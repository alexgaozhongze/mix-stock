<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class HkCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class HkCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            list($microstamp, $timestamp) = explode(' ', microtime());
            $timestamp = "$timestamp" . intval($microstamp * 1000);

            $ql = QueryList::get('http://vip.stock.finance.sina.com.cn/quotes_service/api/json_v2.php/Market_Center.getHKStockCount?node=qbgg_hk');
    
            $response = $ql->getHtml();

            preg_match('/\d+/',$response,$arr);
            $total = reset($arr);

            self::handle($total);
        });
    }

    public function handle($total)
    {
        $page_size = rand(8,88);
        $page_count = ceil($total / $page_size);

        for ($i = 1; $i <= $page_count; $i ++) {
            $urls[] = "http://vip.stock.finance.sina.com.cn/quotes_service/api/json_v2.php/Market_Center.getHKStockData?page=$i&num=$page_size&sort=changepercent&asc=0&node=qbgg_hk&_s_r_a=init";
        }

        $urls = [$urls[0]];

        QueryList::multiGet($urls)
            ->concurrency(8)
            ->withOptions([
                'timeout' => 3
            ])
            ->withHeaders([
                'Content-Type' => 'text/plain; charset=utf-8'
            ])
            ->success(function(QueryList $ql, Response $response, $index) {
                $connection = app()->dbPool->getConnection();
                
                $json_data = $ql->getHtml();
                $json_data = preg_replace('/,(\w+):/is', ',"$1":', $json_data);
                $json_data = str_replace('symbol', '"symbol"', $json_data);
                $json_data = iconv("gb2312","utf-8//IGNORE",$json_data);

                $datas = json_decode($json_data, true);
                if (!$datas) return false;

                $sql_fields = "INSERT INTO `hk` (`code`, `price`, `up`, `upp`, `cjl`, `cje`, `zg`, `zgy`, `zd`, `zdy`, `jk`, `zs`, `date`, `name`, `type`) VALUES ";
                $sql_values = "";

                foreach ($datas as $value) {
                    foreach ($value as $v_key => $v_value) {
                        null === $v_value && $value[$v_key] = 'NULL';
                    }

                    $sql_values && $sql_values .= ',';
                    list($date, $time) = explode (' ', $value['ticktime']);
                    $sql_values .= "($value[symbol], $value[lasttrade], $value[changepercent], $value[pricechange], $value[volume], $value[amount], $value[high], $value[high_52week], $value[low], $value[low_52week], $value[open], $value[prevclose], '$date', '$value[name]', '$value[tradetype]')";
                }

                $sql_duplicate = " ON DUPLICATE KEY UPDATE `price`=VALUES(`price`), `up`=VALUES(`up`), `upp`=VALUES(`upp`), `cjl`=VALUES(`cjl`), `cje`=VALUES(`cje`), `zg`=VALUES(`zg`), `zd`=VALUES(`zd`);";
        
                $sql = $sql_fields . $sql_values . $sql_duplicate;
                $connection->createCommand($sql)->execute();

                // $datas = $item['data'] ?? [];
                // if (!$datas) return false;

                // array_walk($datas, function(&$item, $key) {
                //     '-' == $item && $item = 'NULL';
                //     'f170' == $key && 'NULL' == $item && $item = 0;
                // });

                // list($type, $code) = explode('.', $url_keys[$index]);
                // 0 == $type && $type = 2;

                // $sql_fields = "INSERT IGNORE INTO `$hqbj_table` (`code`, `price`, `up`, `wb`, `wx`, `s5_p`, `s5_n`, `s4_p`, `s4_n`, `s3_p`, `s3_n`, `s2_p`, `s2_n`, `s1_p`, `s1_n`, `b1_p`, `b1_n`, `b2_p`, `b2_n`, `b3_p`, `b3_n`, `b4_p`, `b4_n`, `b5_p`, `b5_n`, `time`, `type`) VALUES ";
                // $sql_values = "";
    
                // $time = date('H:i:s');
                // $sql_values .= "($code, $datas[f43], $datas[f170], $datas[f191], $datas[f192], $datas[f31], $datas[f32], $datas[f33], $datas[f34], $datas[f35], $datas[f36], $datas[f37], $datas[f38], $datas[f39], $datas[f40], $datas[f19], $datas[f20], $datas[f17], $datas[f18], $datas[f15], $datas[f16], $datas[f13], $datas[f14], $datas[f11], $datas[f12], '$time', $type)";
    
                // if ($sql_values) {
                //     $sql = $sql_fields . $sql_values;
                //     $connection->createCommand($sql)->execute();
                // }
            })->error(function (QueryList $ql, $reason, $index){
                // ...
            })->send();

        app()->dbPool->getConnection()->release();
    }

/**
 * http://push2.eastmoney.com/api/qt/clist/get?pi=0&pz=6&po=1&fs=m:116+t:3,m:116+t:4&ut=bd1d9ddb04089700cf9c27f6f7426281&fid=f3&fields=f1,f2,f3,f4,f12,f13,f14,f152&cb=jQuery.jQuery40289975042967185_1570518675192&_=1570518675124
 */

}
