<?php

namespace Console\Commands;

use Console\Libraries\CoroutinePoolStockNewWorker;
use Mix\Concurrent\CoroutinePool\Dispatcher;
use Mix\Console\CommandLine\Flag;
use Mix\Core\Coroutine\Channel;
use GuzzleHttp\Psr7\Response;
use Mix\Helper\ProcessHelper;
use Mix\Core\Event;
use QL\QueryList;

/**
 * Class CoroutinePoolStockDaemonCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class CoroutinePoolStockDaemonCommand
{

    /**
     * 退出
     * @var bool
     */
    public $quit = false;

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            list($microstamp, $timestamp) = explode(' ', microtime());
            $timestamp = "$timestamp" . intval($microstamp * 1000);
    
            $ql = QueryList::get("http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&st=(ChangePercent)&sr=-1&p=1&ps=1&js={%22pages%22:(pc),%22data%22:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=$timestamp");
    
            $json_data = $ql->getHtml();
            $data = json_decode($json_data, true);
            $datas = $data['data'];
            $pages = $data['pages'];
            if (!$datas) return false;
    
            $info = reset($datas);
            $type = explode(',', $info)[0];
            $code = explode(',', $info)[1];
            $date = explode(',', $info)[15];
            if (date('Y-m-d', strtotime($date)) != date('Y-m-d')) return false;

            $redis = app()->redisPool->getConnection();
            while ($redis->brPop(['queryList'], 3)) {}
            
            $page_total = ceil($pages / 50);
            $urls = [];
            for ($i = 1; $i <= $page_total; $i ++) {
                $urls[] = "http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&st=(ChangePercent)&sr=-1&p=$i&ps=50&js={%22pages%22:(pc),%22data%22:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=$timestamp";
            }

            $queue_list = [
                'type' => 1,
                'urls' => $urls
            ];
            $redis->lpush('queryList', serialize($queue_list));

            $page_size = CoroutinePoolStockNewWorker::$fscj_pagesize;
            $urls = [
                "http://mdfm.eastmoney.com/EM_UBG_MinuteApi/Js/Get?dtype=all&token=44c9d251add88e27b65ed86506f6e5da&rows=$page_size&page=1&id=$code$type&gtvolume=&sort=asc&_=$timestamp&js={%22data%22:(x)}"
            ];
            $url_keys = [
                "$code$type"
            ];

            $queue_list = [
                'type' => 2,
                'urls' => $urls,
                'url_keys' => $url_keys
            ];
            
            $redis->lpush('queryList', serialize($queue_list));
        });


        // 守护处理
        $daemon = Flag::bool(['d', 'daemon'], false);
        if ($daemon) {
            ProcessHelper::daemon();
        }
        // 捕获信号
        ProcessHelper::signal([SIGHUP, SIGINT, SIGTERM, SIGQUIT], function ($signal) {
            $this->quit = true;
            ProcessHelper::signal([SIGHUP, SIGINT, SIGTERM, SIGQUIT], null);
        });
        // 协程池执行任务
        xgo(function () {
            $maxWorkers = 8;
            $maxQueue   = 8;
            $jobQueue   = new Channel($maxQueue);
            $dispatch   = new Dispatcher([
                'jobQueue'   => $jobQueue,
                'maxWorkers' => $maxWorkers,
            ]);
            $dispatch->start(CoroutinePoolStockNewWorker::class);
            // 投放任务
            $redis = app()->redisPool->getConnection();
            while (true) {
                if ($this->quit) {
                    $dispatch->stop();
                    return;
                }
                try {
                    $data = $redis->brPop(['queryList'], 3);
                    if (!$data) continue;

                    $queue_list = unserialize(array_pop($data));
                    $htmls = [];

echo $queue_list['type'];

                    QueryList::multiGet($queue_list['urls'])
                    ->concurrency(8)
                    ->withOptions([
                        'timeout' => 3
                    ])
                    ->success(function(QueryList $ql, Response $response, $index) use (&$htmls) {
                        $htmls[$index] = $ql->getHtml();
                    })->error(function (QueryList $ql, $reason, $index){
                        // ...
                    })->send();

                    $push_datas = [
                        'type' => $queue_list['type'], 
                        'data' => $htmls
                    ];
                    2 == $queue_list['type'] && $push_datas['url_keys'] = $queue_list['url_keys'];
                    $jobQueue->push($push_datas);
                } catch (\Throwable $e) {
                    echo $e->getTraceAsString();
                    $dispatch->stop();
                    return;
                }

            }
        });
        // 等待事件
        Event::wait();
    }

}
