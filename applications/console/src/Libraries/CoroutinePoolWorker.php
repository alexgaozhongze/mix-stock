<?php

namespace Console\Libraries;

use Mix\Concurrent\CoroutinePool\AbstractWorker;
use Mix\Concurrent\CoroutinePool\WorkerInterface;

/**
 * Class CoroutinePoolWorker
 * @package Console\Libraries
 * @author alex <alexgaozhongze@gmail.com>
 */
class CoroutinePoolWorker extends AbstractWorker implements WorkerInterface
{
    public static $code_times = [];
    public static $fscj_pagesize = 143;

    /**
     * 初始化事件
     */
    public function onInitialize()
    {
        parent::onInitialize(); // TODO: Change the autogenerated stub
        // 实例化一些需重用的对象
        // ...

        $connection=app()->dbPool->getConnection();
        $table_name = "fscj_" . date('Ymd');
        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            `code` mediumint(6) unsigned zerofill NOT NULL,
            `price` float(5,2) DEFAULT NULL,
            `up` float(5,2) DEFAULT NULL,
            `num` int(11) DEFAULT NULL,
            `bs` tinyint(4) NOT NULL,
            `ud` tinyint(4) NOT NULL,
            `time` time NOT NULL,
            `type` tinyint(4) NOT NULL,
            PRIMARY KEY (`code`,`type`,`time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $connection->createCommand($sql)->execute();
    }

    /**
     * 处理
     * @param $data
     */
    public function handle($data)
    {
        switch ($data['type']) {
            case 1:
                self::zjlx($data['data']);
                break;
            case 2:
                self::fscj($data['data'], $data['url_keys']);
                break;
            case 3:
                self::pkyd($data['data']);
                break;
        }
    }

    private function zjlx($data)
    {
        $connection=app()->dbPool->getConnection();

        $pages = 0;
        array_walk($data, function($item) use ($connection, &$pages) {
            $item = json_decode($item, true);
            $datas = $item['data'];
            $pages = $item['pages'];

            $sql_fields = "INSERT INTO `zjlx` (`code`, `price`, `up`, `mar`, `mai`, `sur`, `sui`, `bir`, `bii`, `mir`, `mii`, `smr`, `smi`, `date`, `name`, `type`) VALUES ";
            $sql_values = "";

            array_walk($datas, function($iitem) use (&$sql_values) {
                $iitem = str_replace('-,', 'NULL,', $iitem);
                $sql_values && $sql_values .= ',';
    
                list($type, $code, $name, $price, $up, $mai, $mar, $sui, $sur, $bii, $bir, $mii, $mir, $smi, $smr, $date_time) = explode(',', $iitem);
                $date = date('Y-m-d', strtotime($date_time));
    
                $sql_values .= "('$code', $price, $up, $mar, $mai, $sur, $sui, $bir, $bii, $mir, $mii, $smr, $smi, '$date', '$name', $type)";
                !isset(self::$code_times[$code . $type]) && self::$code_times[$code . $type] = 0;
            });
    
            $sql_values .= " ON DUPLICATE KEY UPDATE `price`=VALUES(`price`), `up`=VALUES(`up`), `mar`=VALUES(`mar`), `mai`=VALUES(`mai`), `sur`=VALUES(`sur`), `sui`=VALUES(`sui`), `bir`=VALUES(`bir`), `bii`=VALUES(`bii`), `mir`=VALUES(`mir`), `mii`=VALUES(`mii`), `smr`=VALUES(`smr`), `smi`=VALUES(`smi`);";
    
            $sql = $sql_fields . $sql_values;
            $connection->createCommand($sql)->execute();
        });

        usleep(8888888);

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);
        for ($i = 1; $i <= $pages; $i ++) {
            $urls[] = "http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&st=(ChangePercent)&sr=-1&p=$i&ps=50&js={%22pages%22:(pc),%22data%22:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=$timestamp";
        }

        $queue_list = [
            'type' => 1,
            'urls' => $urls
        ];

        $redis = app()->redisPool->getConnection();
        $redis->lpush('queryList', serialize($queue_list));
    }

    private function fscj($data, $url_keys)
    {
        $connection=app()->dbPool->getConnection();
        $table_name = "fscj_" . date('Ymd');

        array_walk($data, function($item, $key) use ($connection, $table_name, $url_keys) {
            $item = json_decode($item, true);
            $datas = $item['data']['value']['data'] ?? [];
            $pc = $item['data']['value']['pc'] ?? 0;

            $sql_fields = "INSERT IGNORE INTO `$table_name` (`code`, `price`, `up`, `num`, `bs`, `ud`, `time`, `type`) VALUES ";
            $sql_values = "";

            $code = substr($url_keys[$key], 0, 6);
            $type = substr($url_keys[$key], 6, 1);

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
        });

        usleep(888888);

        $sql = "SELECT `code`, `type`, COUNT(*) AS count FROM `$table_name` GROUP BY `code`";
        $list = $connection->createCommand($sql)->queryAll();

        $code_times = self::$code_times;
        array_walk($list, function($item) use (&$code_times) {
            $code = str_pad($item['code'], 6, "0", STR_PAD_LEFT);
            $code_times[$code . $item['type']] = $item['count'];
        });
        self::$code_times = $code_times;

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $urls = $url_keys = [];
        array_walk(self::$code_times, function($item, $key) use (&$urls, &$url_keys, $timestamp) {
            $page_size = 143;
            $page = ceil(($item + 1) / $page_size);

            $urls[] = "http://mdfm.eastmoney.com/EM_UBG_MinuteApi/Js/Get?dtype=all&token=44c9d251add88e27b65ed86506f6e5da&rows=$page_size&page=$page&id=$key&gtvolume=&sort=asc&_=$timestamp&js={%22data%22:(x)}";
            $url_keys[] = $key;
        });

        $queue_list = [
            'type' => 2,
            'urls' => $urls,
            'url_keys' => $url_keys
        ];

        $redis = app()->redisPool->getConnection();
        $redis->lpush('queryList', serialize($queue_list));
    }

    private function pkyd($data)
    {
        $connection=app()->dbPool->getConnection();

        $json_data = reset($data);
        $json_data = substr_replace(substr_replace($json_data, '', 0, 1), '', -1, 1);

        $datas = json_decode($json_data, true);
        if ($datas) {
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
    
            $sql = $sql_fields . $sql_values;
            $connection->createCommand($sql)->execute();
        }

        usleep(8888888);

        list($microstamp, $timestamp) = explode(' ', microtime());
        $timestamp = "$timestamp" . intval($microstamp * 1000);

        $urls = [
            "http://nuyd.eastmoney.com/EM_UBG_PositionChangesInterface/api/js?style=top&js=([(x)])&ac=normal&check=itntcd&dtformat=HH:mm:ss&num=20&cb=&_=$timestamp"
        ];

        $queue_list = [
            'type' => 3,
            'urls' => $urls,
        ];
        
        $redis = app()->redisPool->getConnection();
        $redis->lpush('queryList', serialize($queue_list));
    }

}
