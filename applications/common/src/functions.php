<?php

/**
 * 用户助手函数
 * @author alex <alexgaozhongze@gmail.com>
 */

function dates($limit=10)
{
    $connection=app()->dbPool->getConnection();
    $sql = "SELECT `date` FROM `hsab` WHERE `date`<>CURDATE() GROUP BY `date` ORDER BY `date` DESC LIMIT $limit";
    $date_list = $connection->createCommand($sql)->queryAll();

    $list = array_column($date_list, 'date');
    sort($list);

    return $list;
}

function shellPrint($datas)
{
    if (!$datas) {
        echo 'false', PHP_EOL, PHP_EOL;
        return false;
    }

    $data = reset($datas);
    $keys = array_keys($data);
    foreach ($keys as $value) {
        printf("% -10s", $value);
    }
    echo PHP_EOL;

    foreach ($datas as $value) {
        foreach ($value as $vvalue) {
            printf("% -10s", $vvalue);
        }
        echo PHP_EOL;
    }

    echo PHP_EOL;
}