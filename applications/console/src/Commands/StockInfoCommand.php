<?php

namespace Console\Commands;

use Mix\Core\Event;
use Mix\Helper\ProcessHelper;
use Mix\Core\Coroutine\Channel;
use Mix\Console\CommandLine\Flag;
use Mix\Concurrent\CoroutinePool\Dispatcher;
use Console\Libraries\StockInfoCoroutinePoolWorker;

/**
 * Class StockInfoCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class StockInfoCommand
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
            $maxWorkers = 100;
            $maxQueue   = 100;
            $jobQueue   = new Channel($maxQueue);
            $dispatch   = new Dispatcher([
                'jobQueue'   => $jobQueue,
                'maxWorkers' => $maxWorkers,
            ]);
            $dispatch->start(StockInfoCoroutinePoolWorker::class);
            // 投放任务
            while (true) {
                if ($this->quit) {
                    $dispatch->stop();
                    return;
                }

                try {
                    $code = 300591;
                    $jobQueue->push($code);

                    echo time(), PHP_EOL, PHP_EOL;

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
