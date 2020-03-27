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
            // $this->three();
            // $this->four();
            // $this->five();
            $this->six();
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

    // ma5超过1天以上大于ma60 -> ma5低于1.5小时小于ma60 1天48 1.5小时18
    private function four()
    {
        $connection=app()->dbPool->getConnection();
        $start_date = '2019-12-25';
 
        $sql = "SELECT `code`,`ema5`,`ema20` FROM `macd` WHERE `time`>='$start_date'";
        $list = $connection->createCommand($sql)->queryAll();
        
        $pre_code = $day_continue = $hour_continue = 0;
        foreach ($list as $value) {
            if ($pre_code == $value['code']) {
                if ($value['ema5'] >= $value['ema20']) {
                    $day_continue ++;
                } else {
                    if (12 >= $hour_continue) {

                    } else {
                        $day_continue = 0;
                    }
                }
            } else {
                $pre_code = $day_continue = $hour_continue = 0;
            }
        }

    }

    private function five()
    {
        $connection=app()->dbPool->getConnection();
        $redis = app()->redisPool->getConnection();

        $dates = dates(30);
        $start_date = reset($dates);

        $sql = "SELECT `code`,SUM(`up`) AS `sup` FROM `hsab` WHERE `date`>='$start_date' GROUP BY `code` ORDER BY `sup` DESC";
        $list = $connection->createCommand($sql)->queryAll();

        $sort_code = implode(',', array_column($list, 'code'));
        $upstop_start_date = $dates[23];
        $upstop_end_date = $dates[29];

        $sql = "SELECT `code`,`type` FROM `hsab` WHERE `date`>='$upstop_start_date' AND `date`<='$upstop_end_date' AND `up`>=9.9 GROUP BY `code` ORDER BY FIELD(`code`, $sort_code)";
        $list = $connection->createCommand($sql)->queryAll();

        $count = count($list);
        $step = 5;

        $index = $redis->get('index');
        (!$index || $index >= $count - $step) && $redis->setex('index', 3600, 0) && $index = 0;
        $index_end = $index + $step;

        foreach ($list as $key => $value) {
            if ($key >= $index && $key < $index_end) {
                $type = 1 == $value['type'] ? 'sh' : 'sz';
                $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
                $url = "http://quote.eastmoney.com/concept/$type$code.html#fschart-m5k";
                exec("google-chrome $url");
            }
        }

        $redis->setex('index', 3600, $index_end);
    }

    private function six()
    {
        $connection=app()->dbPool->getConnection();

        $dates = dates(8);

        $sql = "SELECT * FROM `date_code` WHERE `date`=CURDATE()";
        $date_code = $connection->createCommand($sql)->queryOne();

        if (!$date_code) {
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

            $min_date = $dates[5];
            $sql = "SELECT `code` FROM `hsab` WHERE `code` IN ($codes) AND `date`>='$min_date' AND `zg`=`zt` GROUP BY `code`";

            $codes_upstop = $connection->createCommand($sql)->queryAll();
            foreach ($codes_upstop as $value) {
                $codes = str_replace([$value['code'] . ',', $value['code']], '', $codes);
            }
            $codes = rtrim($codes, ',');

            $sql = "INSERT INTO `date_code` VALUES (CURDATE(), '$codes')";
            $connection->createCommand($sql)->execute();

            $date_code = [
                'code' => $codes
            ];
        }

        $codes = $date_code['code'];

        $sql = "SELECT `code`,`type` FROM `hsab` WHERE `code` IN ($codes) AND `date`=CURDATE() ORDER BY `up` LIMIT 0,8";
        $list = $connection->createCommand($sql)->queryAll();

        foreach ($list as $value) {
            $market = 1 == $value['type'] ? 1 : 2;
            $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
            $url = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
            exec("google-chrome '$url'");
        }
    }

    private function arraySort($array,$keys,$sort='asc') {
        $newArr = $valArr = array();
        foreach ($array as $key=>$value) {
            $valArr[$key] = $value[$keys];
        }
        ($sort == 'asc') ?  asort($valArr) : arsort($valArr);
        reset($valArr);
        foreach($valArr as $key=>$value) {
            $newArr[$key] = $array[$key];
        }
        return $newArr;
    }

}
