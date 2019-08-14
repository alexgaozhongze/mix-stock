<?php

namespace Console\Commands;

use QL\QueryList;
use Mix\Core\Event;
use Mix\Core\Coroutine\Channel;
use Mix\Concurrent\CoroutinePool\Dispatcher;
use Console\Libraries\CoroutinePoolStockWorker;

/**
 * Class StockCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class StockCommand
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
        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $ql = QueryList::get("http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&st=(ChangePercent)&sr=-1&p=1&ps=1&js={%22pages%22:(pc),%22data%22:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=$timestamp");

        $json_data = $ql->getHtml();
        $data = json_decode($json_data, true);
        $datas = $data['data'];

        if (!$datas) return false;

        $info = reset($datas);
        $date = explode(',', $info)[15];

        if (date('Y-m-d', strtotime($date)) != date('Y-m-d')) return false;

        xgo(function () {
            $maxWorkers = 1;
            $maxQueue   = 8;
            $jobQueue   = new Channel($maxQueue);
            $dispatch   = new Dispatcher([
                'jobQueue'   => $jobQueue,
                'maxWorkers' => $maxWorkers,
            ]);

            $dispatch->start(CoroutinePoolStockWorker::class);

            while(!$this->quit) {
                $jobQueue->push('goBeyond');
                usleep(888888);
                if (time() >= strtotime('15:00')) {
                    $dispatch->stop();
                    $this->quit = true;
                }
            }
        });

        Event::wait();
    }

}
