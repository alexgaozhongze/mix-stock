<?php

namespace Console\Commands;

use QL\QueryList;
use Mix\Core\Event;
use Mix\Redis\RedisConnection;
use Mix\Core\Coroutine\Channel;
use Mix\Concurrent\CoroutinePool\Dispatcher;
use Console\Libraries\CoroutinePoolStockNewWorker;

/**
 * Class StockCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class StockNewCommand
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
            $date = explode(',', $info)[15];
    
            if (date('Y-m-d', strtotime($date)) != date('Y-m-d')) return false;

            $redis = app()->redisPool->getConnection();
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
        });

        xgo(function () {
            $redis = app()->redisPool->getConnection();

            while($datas = $redis->brPop('queryList', 3)) {
                $queue_list = unserialize(array_pop($datas));
                $htmls = [];
                QueryList::multiGet($queue_list['urls'])
                ->concurrency(8)
                ->withOptions([
                    'timeout' => 3
                ])
                ->success(function(QueryList $ql) use (&$htmls) {
                    $htmls[] = $ql->getHtml();
                })->error(function (QueryList $ql, $reason, $index){
                    // ...
                })->send();
            }

            var_export($htmls);
        });

        // list($microstamp, $timestamp) = explode(' ', microtime());
        // $timestamp = "$timestamp" . intval($microstamp * 1000);

        // $ql = QueryList::get("http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&st=(ChangePercent)&sr=-1&p=1&ps=1&js={%22pages%22:(pc),%22data%22:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=$timestamp");

        // $json_data = $ql->getHtml();
        // $data = json_decode($json_data, true);
        // $datas = $data['data'];

        // if (!$datas) return false;

        // $info = reset($datas);
        // $date = explode(',', $info)[15];

        // if (date('Y-m-d', strtotime($date)) != date('Y-m-d')) return false;

        // xgo(function () {
        //     $maxWorkers = 2;
        //     $maxQueue   = 8;
        //     $jobQueue   = new Channel($maxQueue);
        //     $dispatch   = new Dispatcher([
        //         'jobQueue'   => $jobQueue,
        //         'maxWorkers' => $maxWorkers,
        //     ]);

        //     $dispatch->start(CoroutinePoolStockNewWorker::class);

        //     while(!$this->quit) {
        //         $jobQueue->push('goBeyond');
        //         usleep(888888);
        //         if (time() >= strtotime('18:00')) {
        //             $dispatch->stop();
        //             $this->quit = true;
        //         }
        //     }
        // });

        Event::wait();
    }

}
