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
            
            $date = date('Y-m-d');
            $date = date('Y-m-d', strtotime("-1 days"));
            $hqbj_table = 'hqbj_' . date('Ymd', strtotime($date));

            $sql = "SELECT `code`,`type`,`up`,`price` FROM `zjlx` WHERE `date`='$date' AND LEFT(`code`,3) NOT IN (200,300,688,900) AND `price` IS NOT NULL ORDER BY `up` DESC";
            $zjlx_list = $connection->createCommand($sql)->queryAll();

            foreach ($zjlx_list as $zl_value) {
                $sql = "SELECT `b1_n`, `s1_n`, `time` FROM `$hqbj_table` WHERE `code`=$zl_value[code] AND `type`=$zl_value[type]";
                $hqbj_list = $connection->createCommand($sql)->queryAll();

                $sum_b1 = $sum_s1 = $count = 0;
                foreach ($hqbj_list as $hl_value) {
                    if (strtotime($hl_value['time']) <= strtotime('14:57')) {
                        $sum_b1 += $hl_value['b1_n'];
                        $sum_s1 += $hl_value['s1_n'];
                        $count ++;
                    }
                }

                $avg = bcdiv(bcdiv(bcadd($sum_b1, $sum_s1), $count), 2);
                $end_avg = bcdiv($hl_value['b1_n'], $avg, 2);

                if ($end_avg < 100) continue;

                echo str_pad($zl_value['code'], 7)
                    ,str_pad($avg, 8)
                    ,str_pad($hl_value['s1_n'], 8)
                    ,str_pad($zl_value['price'], 7)
                    ,str_pad($zl_value['up'], 7)
                    ,str_pad($end_avg, 4)
                    ,PHP_EOL;
            }
        });

        Event::wait();
    }

}
