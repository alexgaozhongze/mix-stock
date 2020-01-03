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
            // $this->one();
            // $this->two();
            $this->three();
        });

        Event::wait();
    }

    private function one()
    {
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
    }

    private function two()
    {
        $connection=app()->dbPool->getConnection();

        $date = date('Y-m-d');
        // $date = '2019-12-27';
        $sql = "SELECT * FROM `macd` WHERE (`time`='$date 14:15:00' AND `ema5`<`ema60` AND `ema10`<`ema60` AND `ema20`<`ema60` AND `dif`<0 AND `dea`<0) OR (`time`='$date 14:45:00' AND `ema5`>`ema60` AND `ema10`>`ema60` AND `ema20`>`ema60` AND `dif`>0 AND `dea`>0)";
        $code_ema_list = $connection->createCommand($sql)->queryAll();

        $code_exists = [];
        foreach ($code_ema_list as $value) {
            $code_exists[$value['code']]['exists'] = isset($code_exists[$value['code']]) ? 2 : 1;
            $code_exists[$value['code']]['sp'] = $value['sp'];
        }

        foreach ($code_exists as $key => $value) {
            if (2 <= $value['exists']) {
                $sql = "SELECT * FROM `macd` WHERE `code`=$key AND `time`='2019-12-30 09:35:00'";
                $info = $connection->createCommand($sql)->queryOne();
                echo $key, ' ', $value['sp'], ' ', $info['sp'], PHP_EOL;
            }
        }

    }

    private function three()
    {
        $connection=app()->dbPool->getConnection();

        $sql = "SELECT * FROM `macd` WHERE `time`>=CURDATE()";
        $list = $connection->createCommand($sql)->queryAll();

        for ($i = 5; $i < count($list) - 7; $i ++) {
            if (($list[$i]['code'] == $list[$i + 1]['code'] && $list[$i]['code'] == $list[$i + 2]['code'])
            && ($list[$i]['sp'] > $list[$i]['kp'] && $list[$i]['ema5'] >= $list[$i]['ema10'] && $list[$i]['ema10'] >= $list[$i]['ema20'] && $list[$i]['ema20'] >= $list[$i]['ema60'])
            && ($list[$i + 1]['sp'] > $list[$i + 1]['kp'] && $list[$i + 1]['ema5'] >= $list[$i + 1]['ema10'] && $list[$i + 1]['ema10'] >= $list[$i + 1]['ema20'] && $list[$i + 1]['ema20'] >= $list[$i + 1]['ema60'])
            && ($list[$i + 2]['sp'] > $list[$i + 2]['kp'] && $list[$i + 2]['ema5'] >= $list[$i + 2]['ema10'] && $list[$i + 2]['ema10'] >= $list[$i + 2]['ema20'] && $list[$i + 2]['ema20'] >= $list[$i + 2]['ema60'])
            && ($list[$i]['code'] == $list[$i - 1]['code'] && $list[$i]['code'] == $list[$i - 2]['code'] && $list[$i]['code'] == $list[$i - 3]['code'] && $list[$i]['code'] == $list[$i - 4]['code'] && $list[$i]['code'] == $list[$i - 5]['code'])
            && ($list[$i - 1]['ema5'] <= $list[$i - 1]['ema60'] || $list[$i - 2]['ema5'] <= $list[$i - 2]['ema60'] || $list[$i - 3]['ema5'] <= $list[$i - 3]['ema60'] || $list[$i - 4]['ema5'] <= $list[$i - 4]['ema60'] || $list[$i - 5]['ema5'] <= $list[$i - 5]['ema60'])
            && ($list[$i]['code'] == $list[$i + 3]['code'] && $list[$i]['code'] == $list[$i + 4]['code'] && $list[$i]['code'] == $list[$i + 5]['code'] && $list[$i]['code'] == $list[$i + 6]['code'] && $list[$i]['code'] == $list[$i + 7]['code'])
            && ($list[$i + 3]['ema5'] > $list[$i + 2]['ema5'] && $list[$i + 3]['ema10'] > $list[$i + 2]['ema10'] && $list[$i + 3]['ema20'] > $list[$i + 2]['ema20'] && $list[$i + 3]['ema60'] > $list[$i + 2]['ema60'])
            && ($list[$i + 4]['ema5'] > $list[$i + 3]['ema5'] && $list[$i + 4]['ema10'] > $list[$i + 3]['ema10'] && $list[$i + 4]['ema20'] > $list[$i + 3]['ema20'] && $list[$i + 4]['ema60'] > $list[$i + 3]['ema60'])
            && ($list[$i + 5]['ema5'] > $list[$i + 4]['ema5'] && $list[$i + 5]['ema10'] > $list[$i + 4]['ema10'] && $list[$i + 5]['ema20'] > $list[$i + 4]['ema20'] && $list[$i + 5]['ema60'] > $list[$i + 4]['ema60'])
            && ($list[$i + 6]['ema5'] > $list[$i + 5]['ema5'] && $list[$i + 6]['ema10'] > $list[$i + 5]['ema10'] && $list[$i + 6]['ema20'] > $list[$i + 5]['ema20'] && $list[$i + 6]['ema60'] > $list[$i + 5]['ema60'])
            && ($list[$i + 7]['ema5'] > $list[$i + 6]['ema5'] && $list[$i + 7]['ema10'] > $list[$i + 6]['ema10'] && $list[$i + 7]['ema20'] > $list[$i + 6]['ema20'] && $list[$i + 7]['ema60'] > $list[$i + 6]['ema60'])
            ) {
                echo $list[$i]['code'], '   ', $list[$i]['time'], PHP_EOL;
            }
        }

    }

}
