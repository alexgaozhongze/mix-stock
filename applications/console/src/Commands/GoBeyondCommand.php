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
                $sql = "SELECT `code`,`type`,`time` FROM `macd` WHERE `code`=$value[code] AND `type`=$value[type] AND `time`>='$start_date' AND `zd`=$value[zd] ORDER BY `time` DESC";
                $info = $connection->createCommand($sql)->queryOne();

                if (strtotime($info['time']) >= strtotime('2019-12-01')) {
                    var_export($info);
                }
    
                // $next_time = date('Y-m-d H:i:s', strtotime($info['time'] . '+1 days'));
                // $sql = "SELECT * FROM `macd` WHERE `code`=$info[code] AND `type`=$info[type] AND `time`>='$info[time]' AND `time`<='$next_time' AND `dif`>=`dea` AND `dif`>=0.1";
                // $info_up = $connection->createCommand($sql)->queryOne();

                // var_export($info_up);

            }
        });

        Event::wait();
    }

}
