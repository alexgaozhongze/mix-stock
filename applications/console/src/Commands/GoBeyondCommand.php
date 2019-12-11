<?php

namespace Console\Commands;

use Mix\Core\Event;

/**
 * Class GoBeyondCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class GoBeyondCommand
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
            $connection=app()->dbPool->getConnection();
            $code = 2351;

            $sql = "SELECT `dif`,`dea`,`macd` FROM `macd` WHERE `code`=$code";
            $list = $connection->createCommand($sql)->queryAll();

            shellPrint($list);

        });

        Event::wait();
    }

}
