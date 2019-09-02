<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class ZjlxCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class ZjlxCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            list($microstamp, $timestamp) = explode(' ', microtime());
            $timestamp = "$timestamp" . intval($microstamp * 1000);
    
            $ql = QueryList::get("http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&st=(ChangePercent)&sr=-1&p=1&ps=1&js={%22pages%22:(pc),%22data%22:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=$timestamp");
    
            $json_data = $ql->getHtml();
            $data = json_decode($json_data, true);
            $datas = $data['data'] ?? false;
            $pages = $data['pages'] ?? 0;
            if (!$datas) return false;

            $info = reset($datas);
            $date = explode(',', $info)[15];
            if (date('Y-m-d', strtotime($date)) != date('Y-m-d')) return false;

            while (strtotime('09:15') < time() && strtotime('15:15') > time()) {
                self::handle($pages);
                usleep(888888);
            }
        });
    }

    public function handle($pages)
    {
        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $page_size = 50;
        $page_count = ceil($pages / $page_size);
        for ($i = 1; $i <= $page_count; $i ++) {
            $urls[] = "http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&st=(ChangePercent)&sr=-1&p=$i&ps=$page_size&js={%22pages%22:(pc),%22data%22:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=$timestamp";
        }

        QueryList::multiGet($urls)
            ->concurrency(8)
            ->withOptions([
                'timeout' => 3
            ])
            ->success(function(QueryList $ql, Response $response, $index) {
                $connection=app()->dbPool->getConnection();

                $json_data = $ql->getHtml();
                $data = json_decode($json_data, true);
                $datas = $data['data'] ?? false;
                if (!$datas) return false;
        
                $sql_fields = "INSERT INTO `zjlx` (`code`, `price`, `up`, `mar`, `mai`, `sur`, `sui`, `bir`, `bii`, `mir`, `mii`, `smr`, `smi`, `date`, `name`, `type`) VALUES ";
                $sql_values = "";
        
                array_walk($datas, function($iitem) use (&$sql_values) {
                    $iitem = str_replace('-,', 'NULL,', $iitem);
                    $sql_values && $sql_values .= ',';
        
                    list($type, $code, $name, $price, $up, $mai, $mar, $sui, $sur, $bii, $bir, $mii, $mir, $smi, $smr, $date_time) = explode(',', $iitem);
                    $date = date('Y-m-d', strtotime($date_time));
        
                    $sql_values .= "('$code', $price, $up, $mar, $mai, $sur, $sui, $bir, $bii, $mir, $mii, $smr, $smi, '$date', '$name', $type)";
                });
        
                $sql_values .= " ON DUPLICATE KEY UPDATE `price`=VALUES(`price`), `up`=VALUES(`up`), `mar`=VALUES(`mar`), `mai`=VALUES(`mai`), `sur`=VALUES(`sur`), `sui`=VALUES(`sui`), `bir`=VALUES(`bir`), `bii`=VALUES(`bii`), `mir`=VALUES(`mir`), `mii`=VALUES(`mii`), `smr`=VALUES(`smr`), `smi`=VALUES(`smi`);";
        
                $sql = $sql_fields . $sql_values;
                $connection->createCommand($sql)->execute();
        
            })->error(function (QueryList $ql, $reason, $index){
                // ...
            })->send();

        app()->dbPool->getConnection()->release();
    }

}
