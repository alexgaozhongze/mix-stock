<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class YybCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class YybCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            // while (strtotime('09:15') < time() && strtotime('15:15') > time()) {
                self::handle();
                usleep(888888);
            // }
        });
    }

    public function handle()
    {
        $connection=app()->dbPool->getConnection();
        $start_date = date('Y-m-d', strtotime("-2 months"));
        $date = date('Y-m-d');
        $page = 1;
        $page_size = 50;

        $ql = QueryList::get("http://datainterface3.eastmoney.com//EM_DataCenter_V3/api/LHBYYBSBCS/GetLHBYYBSBCS?tkn=eastmoney&mkt=0&dateNum=&startDateTime=$start_date&endDateTime=$date&sortRule=1&sortColumn=JmMoney&pageNum=$page&pageSize=1&cfg=lhbyybsbcs");
        $json_data = $ql->getHtml();
        $decode_contents = json_decode($json_data, true);
        $datas = reset($decode_contents['Data']);

        $total_count = $datas['TotalPage'];
        $field_names = explode(',', $datas['FieldName']);

        foreach ($field_names as $key => $value) {
            switch ($value) {
                case 'YybCode':
                    $ycode_key = $key;
                    break;
                case 'YybName':
                    $yname_key = $key;
                    break;
            }
        }

        $page_count = ceil($total_count / $page_size);
        for ($i = 1; $i <= $page_count; $i ++) {
            
        }



        var_dump($data);die;

        echo $json_data;die;

        $fscj_table = 'fscj_' . date('Ymd');
        $sql = "SELECT `a`.`code`,`a`.`type`,COUNT(`b`.`code`) AS `count` FROM `zjlx` AS `a` LEFT JOIN `$fscj_table` AS `b` ON `a`.`code`=`b`.`code` AND `a`.`type`=`b`.`type` WHERE `a`.`date`=CURDATE() GROUP BY `code`";

        $code_times = $connection->createCommand($sql)->queryAll();
        if (!$code_times) return false;

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $urls = $url_keys = [];
        array_walk($code_times, function($item) use (&$urls, &$url_keys, $timestamp) {
            $page_size = rand(8, 888);
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
