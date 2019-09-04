<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class HqbjCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class HqbjCommand
{

    /*
     * f11 买5
     * f12 数量
     * f13 买4
     * f14 数量
     * f15 买3
     * f16 数量
     * f17 买2
     * f18 数量
     * f19 买1
     * f20 数量
     * f31 卖5
     * f32 数量
     * f33 卖4
     * f34 数量
     * f35 卖3
     * f36 数量
     * f37 卖2
     * f38 数量
     * f39 卖1
     * f40 数量
     * f43 价格
     */
    
    /*
     * f530 = f11,f12,f13,f14,f15,f16,f17,f18,f19,f20,f31,f32,f33,f34,f35,f36,f37,f38,f39,f40
     */

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            $connection=app()->dbPool->getConnection();
            $table_name = "hqbj_" . date('Ymd');
            $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
                `code` mediumint(6) unsigned zerofill NOT NULL,
                `s5` float(5,2) DEFAULT NULL,
                `s4` float(5,2) DEFAULT NULL,
                `s3` float(5,2) DEFAULT NULL,
                `s2` float(5,2) DEFAULT NULL,
                `s1` float(5,2) DEFAULT NULL,
                `b1` float(5,2) DEFAULT NULL,
                `b2` float(5,2) DEFAULT NULL,
                `b3` float(5,2) DEFAULT NULL,
                `b4` float(5,2) DEFAULT NULL,
                `b5` float(5,2) DEFAULT NULL,
                `ud` tinyint(4) NOT NULL,
                `time` time NOT NULL,
                `type` tinyint(4) NOT NULL,
                PRIMARY KEY (`code`,`type`,`time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $connection->createCommand($sql)->execute();

            // while (strtotime('09:15') < time() && strtotime('15:15') > time()) {
                self::handle();
                usleep(888888);
            // }
        });
    }

    public function handle()
    {
        $connection=app()->dbPool->getConnection();

        $hqbj_table = 'hqbj_' . date('Ymd');
        $sql = "SELECT `code` FROM `zjlx` WHERE `date`=CURDATE() AND LEFT(`code`,3) NOT IN (200,300,688,900) GROUP BY `code`";

        $codes = $connection->createCommand($sql)->queryAll();
        if (!$codes) return false;

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $urls = $url_keys = [];
        array_walk($codes, function($item) use (&$urls, &$url_keys, $timestamp) {
            $page_size = rand(8, 888);
            $page = ceil(($item['count'] + 1) / $page_size);

            $key = str_pad($item['code'], 6, "0", STR_PAD_LEFT) . $item['type'];

            $urls[] = "http://push2.eastmoney.com/api/qt/stock/get?ut=fa5fd1943c7b386f172d6893dbfba10b&invt=2&fltt=2&fields=f43,f530&secid=2.000005&cb=jQuery18306333936537019735_1567564218395&_=$timestamp";
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
