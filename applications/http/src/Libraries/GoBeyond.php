<?php

namespace Http\Libraries;

/**
 * Class StockInfoCoroutinePoolWorker
 * @package Console\Libraries
 * @author alex <alexgaozhongze@gmail.com>
 */
class GoBeyond
{

    public static function dates($limit=10)
    {
        $connection=app()->db;
        $sql = "SELECT `date` FROM `hsab` WHERE `date`<>CURDATE() GROUP BY `date` ORDER BY `date` DESC LIMIT $limit";
        $date_list = $connection->createCommand($sql)->queryAll();
    
        $list = array_column($date_list, 'date');
        sort($list);
    
        return $list;
    }

}
