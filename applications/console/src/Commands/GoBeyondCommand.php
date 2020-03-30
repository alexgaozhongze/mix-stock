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
            $dates = dates(1);
            $pre_date = reset($dates);

            $sql = "SELECT * FROM `date_code` WHERE `date`='$pre_date'";
            $date_code = $connection->createCommand($sql)->queryOne();

            $codes = $date_code['code'];

            $sql = "SELECT `code`,`type` FROM `hsab` WHERE `code` IN ($codes) AND `date`=CURDATE() ORDER BY `up` LIMIT 0,8";
            $list = $connection->createCommand($sql)->queryAll();
    
            foreach ($list as $value) {
                $market = 1 == $value['type'] ? 1 : 2;
                $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
                $url = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
                exec("google-chrome '$url'");
            }
        });
    }

}
