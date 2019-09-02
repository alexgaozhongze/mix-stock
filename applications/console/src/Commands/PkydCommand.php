<?php

namespace Console\Commands;

use QL\QueryList;

/**
 * Class PkydCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class PkydCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            while (strtotime('09:15') < time() && strtotime('15:15') > time()) {
                self::handle();
                usleep(888888);
            }
        });
    }

    public function handle()
    {
        $connection=app()->dbPool->getConnection();

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $sql = "SELECT `code` FROM `zjlx` WHERE `date`=CURDATE() LIMIT 1";
        $exists = $connection->createCommand($sql)->queryOne();
        if (!$exists) return false;

        $page_size = rand(8,88);
        $ql = QueryList::get("http://nuyd.eastmoney.com/EM_UBG_PositionChangesInterface/api/js?style=top&js=([(x)])&ac=normal&check=itntcd&dtformat=HH:mm:ss&num=$page_size&cb=&_=$timestamp");

        $json_data = $ql->getHtml();
        $json_data = substr_replace(substr_replace($json_data, '', 0, 1), '', -1, 1);
        $datas = json_decode($json_data, true);

        $sql_fields = "INSERT IGNORE INTO `pkyd` (`code`, `type`, `pkyd_type`, `message`, `time`, `date`) VALUES ";
        $sql_values = "";
        $date = date('Y-m-d');

        array_walk($datas, function($item) use (&$sql_values, $date) {
            list($codeandtype, $time, $name, $message, $nums, $type) = explode(',', $item);
            $sql_values && $sql_values .= ',';

            $code = substr($codeandtype, 0, 6);
            $code_type = substr($codeandtype, 6, 1);

            $sql_values .= "($code, $code_type, $type, '$message,$nums', '$time', '$date')";
        });

        if ($sql_values) {
            $sql = $sql_fields . $sql_values;
            $connection->createCommand($sql)->execute();
        }

        app()->dbPool->getConnection()->release();
    }

}
