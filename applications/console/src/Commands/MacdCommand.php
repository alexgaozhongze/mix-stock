<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class MacdCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class MacdCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            if (!checkOpen()) return false;
            
            while ((strtotime('09:30') <= time() && strtotime('11:35') >= time()) || (strtotime('13:00') <= time() && strtotime('16:00') >= time())) {
                self::handle();
                usleep(88888888);
            }
        });
    }

    public function handle()
    {
        $connection=app()->dbPool->getConnection();

        $table_name = 'hq_' . date('Ymd');
        $sql = "SELECT `code`,`type` FROM `hsab` AS `a` WHERE `date`=CURDATE() AND `price` IS NOT NULL GROUP BY `code`";
        $codes = $connection->createCommand($sql)->queryAll();

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $urls = [];
        array_walk($codes, function($item) use (&$urls, $timestamp) {
            $key = str_pad($item['code'], 6, "0", STR_PAD_LEFT) . $item['type'];
            $urls[] = "http://pdfm.eastmoney.com/EM_UBG_PDTI_Fast/api/js?token=4f1862fc3b5e77c150a2b985b12db0fd&rtntype=6&id=$key&type=m5k&authorityType=&js={%22data%22:(x)}";
        });

        QueryList::multiGet($urls)
            ->concurrency(8)
            ->withOptions([
                'timeout' => 3
            ])
            ->success(function(QueryList $ql, Response $response, $index) use ($connection, $table_name) {
                $response_json = $ql->getHtml();

                $item = json_decode($response_json, true);
                $data = $item['data']['data'] ?? [];
                $info = $item['data']['info'] ?? [];

                $sql_fields = "INSERT IGNORE INTO `macd` (`code`, `kp`, `sp`, `zg`, `zd`, `cjl`, `cje`, `zf`, `time`, `type`) VALUES ";
                $sql_values = "";
    
                $code = $item['data']['code'];
                $type = $info['mk'];
    
                array_walk($data, function($iitem) use (&$sql_values, $code, $type) {
                    list($time, $kp, $sp, $zg, $zd, $cjl, $cje, $zf) = explode(',', $iitem);
                    $zf = floatval($zf);
                    $sql_values && $sql_values .= ',';

                    $sql_values .= "($code, $kp, $sp, $zg, $zd, $cjl, $cje, $zf, '$time', $type)";
                });

                if ($sql_values) {
                    $sql = $sql_fields . $sql_values;
                    $connection->createCommand($sql)->execute();
                }
            })->error(function (QueryList $ql, $reason, $index){
                // ...
            })->send();

        app()->dbPool->getConnection()->release();
    }

}
