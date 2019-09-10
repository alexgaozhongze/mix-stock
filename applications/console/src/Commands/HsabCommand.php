<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class HsabCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class HsabCommand
{
        
    /*
     * f2 price 最新价
     * f3 up 涨跌幅
     * f4 upp 涨跌额
     * f5 cjs 成交手
     * f6 cje 成交额
     * f7 zf 振幅
     * f8 hsl 换手率
     * f9 syl 市盈率
     * f10 lb 量比
     * 
     * 
     * f12 code
     * f13 type
     * f14 name
     * f15 zg 最高
     * f16 zd 最低
     * f17 jk 今开
     * f18 zs 昨收
     * f23 sjl 市净率
     */

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            list($microstamp, $timestamp) = explode(' ', microtime());
            $timestamp = "$timestamp" . intval($microstamp * 1000);

            $ql = QueryList::get("http://50.push2.eastmoney.com/api/qt/clist/get?pn=1&pz=1&po=1&np=1&ut=bd1d9ddb04089700cf9c27f6f7426281&fltt=2&invt=2&fid=f3&fs=m:0+t:6,m:0+t:13,m:0+t:80,m:1+t:2&fields=f2,f3,f4,f5,f6,f7,f8,f9,f10,f12,f13,f14,f15,f16,f17,f18,f23&_=$timestamp");
        
            $json_data = $ql->getHtml();
            $data = json_decode($json_data, true);

            $datas = $data['data']['diff'] ?? false;
            $pages = $data['data']['total'] ?? 0;
            if (!$datas) return false;

            $info = reset($datas);

            while (self::goSync()) {
                self::handle($pages);
                usleep(8888888);
            }
        });
    }

    public function handle($pages)
    {
        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);
        $date = date('Y-m-d');

        $page_size = rand(8,88);
        $page_count = ceil($pages / $page_size);
        for ($i = 1; $i <= $page_count; $i ++) {
            $rand = rand(8,88);
            $urls[] = "http://$rand.push2.eastmoney.com/api/qt/clist/get?pn=$i&pz=$page_size&po=1&np=1&ut=bd1d9ddb04089700cf9c27f6f7426281&fltt=2&invt=2&fid=f3&fs=m:0+t:6,m:0+t:13,m:0+t:80,m:1+t:2&fields=f2,f3,f4,f5,f6,f7,f8,f9,f10,f12,f13,f14,f15,f16,f17,f18,f23&_=$timestamp";
        }

        QueryList::multiGet($urls)
            ->concurrency(8)
            ->withOptions([
                'timeout' => 3
            ])
            ->success(function(QueryList $ql, Response $response, $index) use ($date) {
                $connection = app()->dbPool->getConnection();

                $json_data = $ql->getHtml();
                $data = json_decode($json_data, true);
                $datas = $data['data']['diff'] ?? false;
                if (!$datas) return false;

                $sql_fields = "INSERT INTO `hsab` (`code`, `price`, `up`, `upp`, `cjs`, `cje`, `zf`, `zg`, `zd`, `jk`, `zs`, `lb`, `hsl`, `syl`, `sjl`, `date`, `name`, `type`) VALUES ";
                $sql_values = "";

                foreach ($datas as $value) {
                    foreach ($value as $v_key => $v_value) {
                        '-' === $v_value && $value[$v_key] = 'NULL';
                    }

                    $sql_values && $sql_values .= ',';
                    $type = $value['f13'] ?: 2;
                    $sql_values .= "($value[f12], $value[f2], $value[f3], $value[f4], $value[f5], $value[f6], $value[f7], $value[f15], $value[f16], $value[f17], $value[f18], $value[f10], $value[f8], $value[f9], $value[f23], '$date', '$value[f14]', $type)";
                }
        
                $sql_duplicate = " ON DUPLICATE KEY UPDATE `price`=VALUES(`price`), `up`=VALUES(`up`), `upp`=VALUES(`upp`), `cjs`=VALUES(`cjs`), `cje`=VALUES(`cje`), `zf`=VALUES(`zf`), `zg`=VALUES(`zg`), `zd`=VALUES(`zd`), `jk`=VALUES(`jk`), `zs`=VALUES(`zs`), `lb`=VALUES(`lb`), `hsl`=VALUES(`hsl`), `syl`=VALUES(`syl`), `sjl`=VALUES(`sjl`);";
        
                $sql = $sql_fields . $sql_values . $sql_duplicate;
                $connection->createCommand($sql)->execute();
        
            })->error(function (QueryList $ql, $reason, $index){
                // ...
            })->send();

        app()->dbPool->getConnection()->release();
    }

    private function goSync()
    {
        if (strtotime('09:30') <= time() && strtotime('11:30') >= time()) return true;
        else if (strtotime('13:00') <= time() && strtotime('15:15') >= time()) return true;
    }

}