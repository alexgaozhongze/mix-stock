<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class FscjCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class FscjCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            $connection=app()->dbPool->getConnection();
            $table_name = "fscj_" . date('Ymd');
            $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
                `code` mediumint(6) unsigned zerofill NOT NULL,
                `price` float(5,2) DEFAULT NULL,
                `up` float(5,2) DEFAULT NULL,
                `num` int(11) DEFAULT NULL,
                `bs` tinyint(4) NOT NULL,
                `ud` tinyint(4) NOT NULL,
                `time` time NOT NULL,
                `type` tinyint(4) NOT NULL,
                PRIMARY KEY (`code`,`type`,`time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $connection->createCommand($sql)->execute();

            while (strtotime('09:15') < time() && strtotime('15:15') > time()) {
                self::handle();
                usleep(888888);
            }
        });
    }

    public function handle()
    {
        $connection=app()->dbPool->getConnection();

        $fscj_table = 'fscj_' . date('Ymd');
        $sql = "SELECT `a`.`code`,`a`.`type`,COUNT(`b`.`code`) AS `count` FROM `zjlx` AS `a` LEFT JOIN `$fscj_table` AS `b` ON `a`.`code`=`b`.`code` AND `a`.`type`=`b`.`type` WHERE `a`.`date`=CURDATE() GROUP BY `code`";

        $code_times = $connection->createCommand($sql)->queryAll();
        if (!$code_times) return false;

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $urls = $url_keys = [];
        array_walk($code_times, function($item) use (&$urls, &$url_keys, $timestamp) {
            $page_size = 144;
            $page = ceil(($item['count'] + 1) / $page_size);

            $key = str_pad($item['code'], 6, "0", STR_PAD_LEFT) . $item['type'];

            $urls[] = "http://mdfm.eastmoney.com/EM_UBG_MinuteApi/Js/Get?dtype=all&token=44c9d251add88e27b65ed86506f6e5da&rows=$page_size&page=$page&id=$key&gtvolume=&sort=asc&_=$timestamp&js={%22data%22:(x)}";
            $url_keys[] = $key;
        });

        QueryList::multiGet($urls)
            ->concurrency(8)
            ->withOptions([
                'timeout' => 3
            ])
            ->success(function(QueryList $ql, Response $response, $index) use ($connection, $fscj_table, $url_keys) {
                $data = $ql->getHtml();

                $item = json_decode($data, true);
                $datas = $item['data']['value']['data'] ?? [];
                $pc = $item['data']['value']['pc'] ?? 0;
    
                $sql_fields = "INSERT IGNORE INTO `$fscj_table` (`code`, `price`, `up`, `num`, `bs`, `ud`, `time`, `type`) VALUES ";
                $sql_values = "";
    
                $code = substr($url_keys[$index], 0, 6);
                $type = substr($url_keys[$index], 6, 1);
    
                array_walk($datas, function($iitem) use (&$sql_values, $pc, $code, $type) {
                    list($time, $price, $num, $bs, $ud) = explode(',', $iitem);
                    $sql_values && $sql_values .= ',';
                    $up = bcmul(bcdiv(bcsub($price, $pc, 2), $pc, 4), 100, 2);
    
                    $sql_values .= "('$code', $price, $up, $num, $bs, $ud, '$time', $type)";
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
