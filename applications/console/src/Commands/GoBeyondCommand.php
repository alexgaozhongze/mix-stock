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

                    } else {
                        if ($value['dif'] >= 0.99 * $value['dea'] && $value['dif'] <= 1.01 * $value['dea']) {
                            $date_pre_price[$date]['cross'] = $value;
                        }
                    }

                    $date_pre_price[$date]['pre'] = $value;
                }

            }



            // shellPrint($list);
        });

        Event::wait();
    }

}
