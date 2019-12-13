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
    private $connection;

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            $connection=app()->dbPool->getConnection();
            
            $code = 2962;

            $sql = "SELECT `dif`,`dea`,`macd`,`time` FROM `macd` WHERE `code`=$code";
            $list =$connection->createCommand($sql)->queryAll();

            



            shellPrint($list);

        });

        Event::wait();
    }

}
