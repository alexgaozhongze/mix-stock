<?php

namespace Console\Commands;

use Mix\Core\Event;

/**
 * Class DateCodeCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class DateCodeCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            $connection = app()->dbPool->getConnection();

            $sql = "SELECT `code` FROM `hsab` WHERE `date`=CURDATE() LIMIT 1";
            $list = $connection->createCommand($sql)->queryAll();
            if (!$list) {
                return false;
            }

            $dates = dates(7);
            $dates[] = date('Y-m-d');

            $sql = "SELECT `code`,`type` FROM `hsab` WHERE `date`=CURDATE() AND `price` IS NOT NULL AND LEFT(`code`,3) NOT IN (200,300,688,900) AND LEFT(`name`, 1) NOT IN ('*', 'S') AND RIGHT(`name`, 1)<>'退'";
            $code_list = $connection->createCommand($sql)->queryAll();
    
            $codes = '';
            foreach ($code_list as $value) {
                $i = 0;
                foreach ($dates as $date) {
                    $sql = "SELECT `zd` FROM `macd` WHERE `code`=$value[code] AND `time`='$date 09:35:00'";
                    $info = $connection->createCommand($sql)->queryOne();
                    $kp = $info['zd'];
    
                    $sql = "SELECT `sp` FROM `macd` WHERE `code`=$value[code] AND `time`='$date 15:00:00'";
                    $info = $connection->createCommand($sql)->queryOne();
                    $sp = $info['sp'];
                    
                    if ($sp > $kp) {
                        $i ++;
                    } else {
                        break;
                    }
                }
    
                if ($i == count($dates)) {
                    $codes .= $value['code'] . ',';
                }
            }
            $codes = rtrim($codes, ',');

            // $min_date = $dates[5];
            // $sql = "SELECT `code` FROM `hsab` WHERE `code` IN ($codes) AND `date`>='$min_date' AND `zg`=`zt` GROUP BY `code`";

            // $codes_upstop = $connection->createCommand($sql)->queryAll();
            // foreach ($codes_upstop as $value) {
            //     $codes = str_replace([$value['code'] . ',', $value['code']], '', $codes);
            // }
            // $codes = rtrim($codes, ',');

            $sql = "INSERT INTO `date_code` VALUES (CURDATE(), '$codes')";
            $connection->createCommand($sql)->execute();
        });

        Event::wait();
    }

}
