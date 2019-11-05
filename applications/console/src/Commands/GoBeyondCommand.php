<?php

namespace Console\Commands;

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
    public $quit = false;

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            while ((strtotime('09:30') <= time() && strtotime('15:15') >= time())) {
                self::handle();
                sleep(88);
                echo PHP_EOL,PHP_EOL,PHP_EOL;
            }
        });

        Event::wait();
    }

    private function handle()
    {
        $connection=app()->dbPool->getConnection();

        $sql = "select code from hsab where date=curdate() order by up desc";
        $list = $connection->createCommand($sql)->queryAll();
        $code_list = array_column($list, 'code');
        $date_list = dates(5, 'Ymd');

        $result_list = [];
        foreach ($code_list as $code) {
            $result = [];
            $result[] = $code;

            $pre_continuous = 0;
            $max_pre_continuous = 0;
            foreach ($date_list as $date) {
                $sql = "select * from hq_$date where code=$code";
                $list = $connection->createCommand($sql)->queryAll();
                $up_count = 0;
                $pre = 0;
                foreach ($list as $lvalue) {
                    $lvalue['price'] > $lvalue['aprice'] && $up_count ++;
                }
                if (120 < $up_count) {
                    $pre = $up_count / 240 * 100;
                } else if (120 > $up_count) {
                    $pre = ($up_count - 240) / 240 * 100;
                }
                $pre = round($pre, 2);
                if (80 <= $pre) {
                    $pre_continuous ++;
                    $pre_continuous >= $max_pre_continuous && $max_pre_continuous = $pre_continuous;
                } else {
                    $pre_continuous = 0;
                }
                $result[] = $pre;

                $sql = "select up from hsab where code=$code and date=date_format($date,'%Y-%m-%d')";
                $info = $connection->createCommand($sql)->queryOne();
                $result[] = $info['up'];
            }

            $date = date('Ymd');
            $sql = "select * from hq_$date where code=$code";
            $list = $connection->createCommand($sql)->queryAll();
            $up_count = 0;
            $count = 0;
            $pre = 0;
            foreach ($list as $lvalue) {
                $count ++;
                $lvalue['price'] > $lvalue['aprice'] && $up_count ++;
            }
            if ($up_count > $count / 2) {
                $pre = $up_count / $count * 100;
            } else if ($up_count < $count / 2) {
                $pre = ($up_count - $count) / $count * 100;
            }
            $pre = round($pre, 2);
            $result[] = $pre;

            $sql = "select up from hsab where code=$code and date=curdate()";
            $info = $connection->createCommand($sql)->queryOne();
            $result[] = $info['up'];
            $result[] = $max_pre_continuous;

            $result_list[] = $result;
        }

        foreach ($result_list as $value) {
            if (3 <= $value[13]) {
                echo str_pad($value[0], 6, 0, STR_PAD_LEFT);
                for ($i=1; $i<=12; $i++) {
                    echo str_pad($value[$i], 8, ' ', STR_PAD_LEFT);
                }
                echo str_pad($value[13], 6, ' ', STR_PAD_LEFT);
                echo PHP_EOL;
            }
        }
    }

}
