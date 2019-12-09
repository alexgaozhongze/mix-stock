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
            $dates = dates(30);
            $start_date = reset($dates);

            $connection=app()->dbPool->getConnection();
            // `dif`>=`dea` AND `dea`>=`macd` AND `macd`>=1
            $sql = "SELECT `code`,`type`,MIN(`zd`) AS `zd` FROM `macd` WHERE `time`>='$start_date' GROUP BY `code`";
            $code_list = $connection->createCommand($sql)->queryAll();

            foreach ($code_list as $value) {
                $sql = "SELECT * FROM `macd` WHERE `code`=$value[code] AND `type`=$value[type] AND `time`>='$start_date' AND `zd`=$value[zd] ORDER BY `time` DESC";
                $info = $connection->createCommand($sql)->queryOne();
    
                var_export($info);
            }
        });

        Event::wait();
    }

}
