<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;

/**
 * Class HelloCommand
 * @package Console\Commands
 * @author liu,jian <coder.keda@gmail.com>
 */
class TestCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            $connection=app()->dbPool->getConnection();

            $dates = dates(35);
            $dates[] = date('Y-m-d');

            foreach ($dates as $value) {
                $fscj_table = "fscj_" . date('Ymd', strtotime($value));
                $sql = "select sum(num) as sum from $fscj_table where bs=4 and code=2505";
                $jj_sum = $connection->createCommand($sql)->queryOne();
                
                $sql = "select count(*) as count from $fscj_table where code=2505";
                $fj_count = $connection->createCommand($sql)->queryOne();

                $sql = "select up,price from hsab where code=2505 and date='$value'";
                $code_info = $connection->createCommand($sql)->queryOne();

                echo str_pad($value, 12, ' ', STR_PAD_RIGHT),
                     str_pad($jj_sum['sum'], 6, ' ', STR_PAD_LEFT),
                     str_pad($fj_count['count'], 7, ' ', STR_PAD_LEFT),
                     str_pad($code_info['up'], 8, ' ', STR_PAD_LEFT),
                     str_pad($code_info['price'], 7, ' ', STR_PAD_LEFT),
                     PHP_EOL;
            }


            /**
             * 买到涨停bs=2多少手
             * 涨停价出货多少手
             * 涨停不开版平均每分钟多少手
             */


        });
    }

}
