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
     * f170 涨幅
     * f191 委比
     * f192 委差
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
                `price` float(6,2) DEFAULT NULL,
                `up` float(5,2) DEFAULT NULL,
                `wb` float(5,2) DEFAULT NULL,
                `wx` float(8,2) DEFAULT NULL,
                `s5_p` float(6,2) DEFAULT NULL,
                `s5_n` float(8,2) DEFAULT NULL,
                `s4_p` float(6,2) DEFAULT NULL,
                `s4_n` float(8,2) DEFAULT NULL,
                `s3_p` float(6,2) DEFAULT NULL,
                `s3_n` float(8,2) DEFAULT NULL,
                `s2_p` float(6,2) DEFAULT NULL,
                `s2_n` float(8,2) DEFAULT NULL,
                `s1_p` float(6,2) DEFAULT NULL,
                `s1_n` float(8,2) DEFAULT NULL,
                `b1_p` float(6,2) DEFAULT NULL,
                `b1_n` float(8,2) DEFAULT NULL,
                `b2_p` float(6,2) DEFAULT NULL,
                `b2_n` float(8,2) DEFAULT NULL,
                `b3_p` float(6,2) DEFAULT NULL,
                `b3_n` float(8,2) DEFAULT NULL,
                `b4_p` float(6,2) DEFAULT NULL,
                `b4_n` float(8,2) DEFAULT NULL,
                `b5_p` float(6,2) DEFAULT NULL,
                `b5_n` float(8,2) DEFAULT NULL,
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

        $hqbj_table = 'hqbj_' . date('Ymd');
        $sql = "SELECT `code`,`type` FROM `zjlx` WHERE `date`=CURDATE() AND LEFT(`code`,3) NOT IN (200,300,688,900) AND `price` IS NOT NULL GROUP BY `code`";

        $codes = $connection->createCommand($sql)->queryAll();
        if (!$codes) return false;

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $urls = $url_keys = [];
        array_walk($codes, function($item) use (&$urls, &$url_keys, $timestamp) {
            $type = 2 == $item['type'] ? 0 : 1;
            $key = $type . '.' . str_pad($item['code'], 6, "0", STR_PAD_LEFT);

            $urls[] = "http://push2.eastmoney.com/api/qt/stock/get?ut=fa5fd1943c7b386f172d6893dbfba10b&invt=2&fltt=2&fields=f43,f170,f191,f192,f530&secid=$key&_=$timestamp";
            $url_keys[] = $key;
        });

        QueryList::multiGet($urls)
            ->concurrency(8)
            ->withOptions([
                'timeout' => 3
            ])
            ->success(function(QueryList $ql, Response $response, $index) use ($connection, $hqbj_table, $url_keys) {
                $data = $ql->getHtml();

                $item = json_decode($data, true);

                $datas = $item['data'] ?? [];
                if (!$datas) return false;

                array_walk($datas, function(&$item, $key) {
                    '-' == $item && $item = 'NULL';
                    'f170' == $key && 'NULL' == $item && $item = 0;
                });

                list($type, $code) = explode('.', $url_keys[$index]);
                0 == $type && $type = 2;

                $sql_fields = "INSERT IGNORE INTO `$hqbj_table` (`code`, `price`, `up`, `wb`, `wx`, `s5_p`, `s5_n`, `s4_p`, `s4_n`, `s3_p`, `s3_n`, `s2_p`, `s2_n`, `s1_p`, `s1_n`, `b1_p`, `b1_n`, `b2_p`, `b2_n`, `b3_p`, `b3_n`, `b4_p`, `b4_n`, `b5_p`, `b5_n`, `time`, `type`) VALUES ";
                $sql_values = "";
    
                $time = date('H:i:s');
                $sql_values .= "($code, $datas[f43], $datas[f170], $datas[f191], $datas[f192], $datas[f31], $datas[f32], $datas[f33], $datas[f34], $datas[f35], $datas[f36], $datas[f37], $datas[f38], $datas[f39], $datas[f40], $datas[f19], $datas[f20], $datas[f17], $datas[f18], $datas[f15], $datas[f16], $datas[f13], $datas[f14], $datas[f11], $datas[f12], '$time', $type)";
    
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
