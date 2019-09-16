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
            
            $sql = "SELECT `code`,`type` FROM `hsab` WHERE `date`='2019-09-11' and up>=9.9";
            $list = $connection->createCommand($sql)->queryAll();

            var_dump($list);

        });

        Event::wait();
    }

}
