<?php

namespace Console\Commands;

use QL\QueryList;
use Mix\Core\Event;
use Mix\Core\Coroutine\Channel;
use Mix\Concurrent\CoroutinePool\Dispatcher;
use Console\Libraries\CoroutinePoolStockWorker;

/**
 * Class TestCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class TestCommand
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

        $ql = QueryList::get("http://nuyd.eastmoney.com/EM_UBG_PositionChangesInterface/api/js?style=top&js=([(x)])&ac=normal&check=itntcd&dtformat=HH:mm:ss&num=20&cb=&_=$timestamp");

        $json_data = $ql->getHtml();
        $data = substr_replace($json_data, '{', 0, 1);
        $data = substr_replace($data, '}', -1, 1);

        echo $data;


        // $data = json_decode($json_data, true);
        // $datas = $data['data'];

        // if (!$datas) return false;

        // $info = reset($datas);
        // $date = explode(',', $info)[15];

        // if (date('Y-m-d', strtotime($date)) != date('Y-m-d')) return false;

        // xgo(function () {
        //     $maxWorkers = 1;
        //     $maxQueue   = 8;
        //     $jobQueue   = new Channel($maxQueue);
        //     $dispatch   = new Dispatcher([
        //         'jobQueue'   => $jobQueue,
        //         'maxWorkers' => $maxWorkers,
        //     ]);

        //     $dispatch->start(CoroutinePoolStockWorker::class);

        //     while(!$this->quit) {
        //         $jobQueue->push('goBeyond');
        //         usleep(888888);
        //         if (time() >= strtotime('18:00')) {
        //             $dispatch->stop();
        //             $this->quit = true;
        //         }
        //     }
        // });

        // Event::wait();
    }

}
