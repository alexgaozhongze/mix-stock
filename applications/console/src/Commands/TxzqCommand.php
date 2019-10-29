<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class TxzqCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class TxzqCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            // https://qt.gtimg.cn/r=0.47503966397200337&q=sh000001,sz399001,r_hkHSI
            $response = QueryList::get('https://stock.gtimg.cn/data/index.php?appn=rank&t=ranka/chr&p=1&o=0&l=10000&v=list_data')->getHtml();
            preg_match('#data:\'.*\'}#isU', $response, $datas);
            $datas = preg_replace(['/data:/','/\'/','/}/'], '', reset($datas));
            $codes = explode(',', $datas);

            $response = QueryList::get('https://stock.gtimg.cn/data/hk_rank.php?board=main_all&metric=price&pageSize=10000&reqPage=1&order=decs&var_name=list_data')->getHtml();
            preg_match_all('#"\d.*~#isU', $response, $datas);
            $codes_hk = preg_replace(['/"/','/~/'], ['hk',''], reset($datas));
            $codes = array_merge($codes, $codes_hk);

            $step = 100;
            $count = count($codes);
            $groups = ceil($count/$step);
            $codes_groups = [];
            for ($i=0; $i<$groups; $i++) {
                $codes_groups[] = array_slice($codes, $i*$step, $step);
            }

            self::handle($codes_groups);
        });
    }

    public function handle($codes_groups)
    {
        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);
        
        $urls = [];
        foreach ($codes_groups as $value) {
            $urls[] = 'https://qt.gtimg.cn/q=' . implode(',', $value) . '&r=' . $timestamp;
        }

        $urls = [$urls[0]];

        QueryList::multiGet($urls)
            ->concurrency(8)
            ->withOptions([
                'timeout' => 3
            ])
            ->success(function(QueryList $ql, Response $response, $index) {
                $connection = app()->dbPool->getConnection();
                
                $data_string = $ql->getHtml();
                $data_string = iconv("gb2312","utf-8//IGNORE",$data_string);
                preg_match_all('#v_.*;#isU', $data_string, $datas);
                $datas = preg_replace(['/v_/','/=/','/"/','/;/'], ['','~','',''], reset($datas));

                var_dump($datas);
                // echo $data_string;die;

                // $data_array = explode(';', $data_string);

                // var_dump($data_array);
            })->error(function (QueryList $ql, $reason, $index){
                // ...
            })->send();

        app()->dbPool->getConnection()->release();
    }

}