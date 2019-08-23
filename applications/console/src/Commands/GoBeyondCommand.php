<?php

namespace Console\Commands;

use QL\QueryList;
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
            
            $sql = "SELECT * FROM `pkyd` WHERE `date`=CURDATE() AND `time`<='10:00:00'";
            $list = $connection->createCommand($sql)->queryAll();

            $coded_list = [];
            array_walk($list, function($item) use (&$coded_list) {
                $coded_list[$item['code']][] = $item;
            });

            var_export($coded_list);

            // array_walk($coded_list, function($item, $key) {
            //     var_export(array_slice($item, 0, 3));
            // });

        });

        Event::wait();
    }

}
