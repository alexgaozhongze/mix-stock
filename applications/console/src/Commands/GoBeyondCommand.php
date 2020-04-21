<?php

namespace Console\Commands;

use Mix\Console\CommandLine\Flag;
use Mix\Core\Event;

/**
 * Class GoBeyondCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class GoBeyondCommand
{

    /**
     * 退出
     * @var bool
     */
    private $connection;

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            $connection=app()->dbPool->getConnection();
            $redis=app()->redisPool->getConnection();

            $dates = dates(8);

            $sql = "select date_code.date,hsab.* from date_code left join hsab on FIND_IN_SET(hsab.code,date_code.code) and date_code.date<=hsab.date where date_code.date>='$dates[0]' and hsab.up>=9.9 group by concat(hsab.date,hsab.code) order by hsab.date";
            $list = $connection->createCommand($sql)->queryAll();

            $index = $redis->get('index');
            $index >= count($list) && $redis->setex('index', 88888, 0) && $index = 0;
            $index_end = $index + 8;

            foreach ($list as $key => $value) {
                if ($key <= $index_end && $key >= $index) {
                    $market = 1 == $value['type'] ? 1 : 2;
                    $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
                    $url = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
                    exec("google-chrome '$url'");
                } else {
                    continue;
                }
            }

            $index = $index_end + 1;
            $redis->setex('index', 88888, $index);
        });
    }

}
