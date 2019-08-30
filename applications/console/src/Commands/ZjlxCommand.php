<?php

namespace Console\Commands;

use QL\QueryList;

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
            if (!$datas) return false;

            $info = reset($datas);
            $date = explode(',', $info)[15];
            if (date('Y-m-d', strtotime($date)) != date('Y-m-d')) return false;

            self::handle();
        });
    }

    public function handle()
    {
        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $ql = QueryList::get("http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&st=(ChangePercent)&sr=-1&p=1&ps=10000&js={%22pages%22:(pc),%22data%22:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=$timestamp");

        $json_data = $ql->getHtml();
        $data = json_decode($json_data, true);
        $datas = $data['data'] ?? false;
        if (!$datas) return false;

        $connection=app()->dbPool->getConnection();
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

        app()->dbPool->getConnection()->release();
    }

}
