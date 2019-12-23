<?php

namespace Console\Commands;

use Mix\Console\CommandLine\Flag;
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

            $sql = "SELECT `code` FROM hsab WHERE `date`=CURDATE() AND `price` IS NOT NULL AND LEFT(`code`,3) NOT IN (200,300,688,900) AND LEFT(`name`, 1) NOT IN ('*', 'S') AND RIGHT(`name`, 1)<>'退'";
            $code_list = $connection->createCommand($sql)->queryAll();
            $count = count($code_list);
            $rand = rand(0, $count-1);
            
            $rand_code = $code_list[$rand]['code'];
            $code = $rand_code;
            $code  = Flag::string('code', $rand_code);

            echo $code, PHP_EOL;

            $sql = "SELECT `dif`,`dea`,`macd`,`time` FROM `macd` WHERE `code`=$code";
            $list = $connection->createCommand($sql)->queryAll();

            $date_cross = [];

            foreach ($list as $value) {
                $date = date('Ymd', strtotime($value['time']));
                if (!isset($date_cross[$date])) {
                    $date_cross[$date] = [
                        'cross' => [],
                        'cross_dif_avg' => 0,
                        'cross_dea_avg' => 0
                    ];
                } else {
                    $cross = $date_cross[$date]['cross'];
                    
                    if ($cross) {
                        if ($value['dif'] >= $date_cross[$date]['cross_dif_avg'] && $value['dea'] >= $date_cross[$date]['cross_dea_avg']) {
                            $date_cross[$date]['cross'][] = $value;
                            $date_cross[$date]['cross_dif_avg'] = round(($date_cross[$date]['cross_dif_avg'] + $value['dif']) / 2, 3);
                            $date_cross[$date]['cross_dea_avg'] = round(($date_cross[$date]['cross_dea_avg'] + $value['dea']) / 2, 3);
                        } else {
                            $date_cross[$date] = [
                                'cross' => [],
                                'cross_dif_avg' => 0,
                                'cross_dea_avg' => 0,
                            ];
                        }
                    } else {
                        if ($value['dif'] <= $value['dea'] + 0.001 && $value['dif'] >= $value['dea'] - 0.001) {
                            $date_cross[$date]['cross'][] = $value;
                            $date_cross[$date]['cross_dif_avg'] = $value['dif'];
                            $date_cross[$date]['cross_dea_avg'] = $value['dea'];
                        }
                    }

                    $date_cross[$date]['pre'] = $value;
                }
            }

            var_export($date_cross);
            echo $code,PHP_EOL;
        });

        Event::wait();
    }

}
