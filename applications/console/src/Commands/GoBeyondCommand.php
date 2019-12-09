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
            $sql = "SELECT * FROM `macd` WHERE `dif`>=`dea` AND `dif`>0 AND `dea`>0 AND DATE_FORMAT(`time`, \"%H:%i:%s\")='15:00:00' AND DATE_FORMAT(`time`, \"%Y:%b:%c\")='2019-12-01'";
            $code_list = $connection->createCommand($sql)->queryAll();

            shellPrint($code_list);
        });

        Event::wait();
    }

}
