<?php

namespace Console\Commands;

/**
 * Class FscjCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 * http://1.push2.eastmoney.com/api/qt/clist/get?cb=jQuery112405026647529725075_1567749029845&pn=187&pz=20&po=1&np=1&ut=bd1d9ddb04089700cf9c27f6f7426281&fltt=2&invt=2&fid=f3&fs=m:0+t:6,m:0+t:13,m:0+t:80,m:1+t:2&fields=f1,f2,f3,f4,f5,f6,f7,f8,f9,f10,f12,f13,f14,f15,f16,f17,f18,f20,f21,f23,f24,f25,f22,f11,f62,f128,f136,f115,f152&_=1567749029872
 */
class FscjAvgCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            $connection=app()->dbPool->getConnection();

            $date = date('Y-m-d');
            $table_name = "fscj_" . date('Ymd', strtotime($date));
            
            $sql = "SELECT `code`,`type` FROM `$table_name` GROUP BY `code`";
            $code_list = $connection->createCommand($sql)->queryAll();

            foreach ($code_list as $value) {
                $sql = "SELECT `price`,`up`,`num`,`bs`,`ud` FROM `$table_name` WHERE `code`=$value[code] AND `type`=$value[type]";
                $list = $connection->createCommand($sql)->queryAll();

                $count = count($list);
                $avg_price = bcdiv(array_sum(array_column($list, 'price')), $count, 2);
                $avg_up = bcdiv(array_sum(array_column($list, 'up')), $count, 2);
                $avg_num = bcdiv(array_sum(array_column($list, 'num')), $count);
                $avg_bs = bcdiv(array_sum(array_column($list, 'bs')), $count, 2);
                $avg_ud = bcdiv(array_sum(array_column($list, 'ud')), $count, 2);
                
                $sql = "INSERT INTO `fscj_avg` VALUES ($value[code], $avg_price, $avg_up, $avg_num, $avg_bs, $avg_ud, '$date', $value[type])";
                $connection->createCommand($sql)->execute();
            }
        });
    }

}
