<?php

namespace Console\Commands;

use QL\QueryList;
use Mix\Core\Event;

/**
 * Class TestCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class TestCommand
{

    /**
     * 退出
     * @var bool
     */
    public $quit = false;

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {

            $connection=app()->dbPool->getConnection();

            list($microstamp, $timestamp) = explode(' ', microtime());
            $timestamp = "$timestamp" . intval($microstamp * 1000);
    
            $ql = QueryList::get("http://nuyd.eastmoney.com/EM_UBG_PositionChangesInterface/api/js?style=top&js=([(x)])&ac=normal&check=itntcd&dtformat=HH:mm:ss&num=20&cb=&_=$timestamp");
    
            $json_data = $ql->getHtml();
            $json_data = substr_replace(substr_replace($json_data, '', 0, 1), '', -1, 1);
    
            $datas = json_decode($json_data, true);
    
            $sql_fields = "INSERT IGNORE INTO `pkyd` (`code`, `type`, `pkyd_type`, `message`, `time`) VALUES ";
            $sql_values = "";
    
            array_walk($datas, function($item) use (&$sql_values) {
                list($codeandtype, $time, $name, $message, $nums, $type) = explode(',', $item);
                $sql_values && $sql_values .= ',';

                $code = substr($codeandtype, 0, 6);
                $code_type = substr($codeandtype, 6, 1);
    
                $sql_values .= "($code, $code_type, $type, '$message,$nums', '$time')";
            });
    
            if ($sql_values) {
                $sql = $sql_fields . $sql_values;
                $connection->createCommand($sql)->execute();
            }

        });

        Event::wait();
    }

}
