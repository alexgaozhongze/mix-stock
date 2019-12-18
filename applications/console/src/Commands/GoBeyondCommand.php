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

            $date_pre_price = [];

            foreach ($list as $value) {
                $date = date('Ymd', strtotime($value['time']));
                if (!isset($date_pre_price[$date])) {
                    $date_pre_price[$date] = [
                        'pre' => $value,
                        'cross' => []
                    ];
                } else {
                    $pre_value = $date_pre_price[$date]['pre'];
                    $cross = $date_pre_price[$date]['cross'];
                    
                    if ($cross) {
                        if ($value['dea'] >= 0.99 * $pre_value['dea']) {
                            $date_pre_price[$date]['cross'][] = $value;
                        } else {
                            $date_pre_price[$date]['cross'] = [];
                        }
                    } else {
                        if ($value['dif'] >= 0.99 * $value['dea'] && $value['dif'] <= 1.01 * $value['dea']) {
                            $date_pre_price[$date]['cross'][] = $value;
                        }
                    }

                    $date_pre_price[$date]['pre'] = $value;
                }
            }

            var_dump($date_pre_price);
            echo $code;
        });

        Event::wait();
    }

}
