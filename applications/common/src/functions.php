<?php

/**
 * 用户助手函数
 * @author alex <alexgaozhongze@gmail.com>
 */

function dates($limit=10)
{
    $connection=app()->dbPool->getConnection();
    $sql = "SELECT `date` FROM `hsab` GROUP BY `date` LIMIT $limit";
    $date_list = $connection->createCommand($sql)->queryAll();

    return [
        '2019-09-02',
        '2019-09-03',
        '2019-09-04',
        '2019-09-05',
        '2019-09-06',
        '2019-09-09',
        '2019-09-10',
        '2019-09-11',
        '2019-09-12',
        '2019-09-16',
        '2019-09-17'
    ];

    return array_column($date_list, 'date');
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