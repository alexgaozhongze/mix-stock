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
            $connection=app()->dbPool->getConnection();

            $code = 2157;
            $sql = "select * from hq_20191016 where code=$code
                    union
                    select * from hq_20191017 where code=$code
                    union
                    select * from hq_20191018 where code=$code";
            $list = $connection->createCommand($sql)->queryAll();

            $num = 0;
            $high_num = 0;
            foreach ($list as $key => $value) {
                $num ++;
                unset($list[$key]['type']);
                
                $high = 0;
                if ($value['price'] >= $value['aprice']) {
                    $high = 1;
                    $high_num ++;
                }
                $list[$key]['high'] = $high;
                $list[$key]['hpre'] = round($high_num / $num * 100, 2);
            }


            // var_dump($list);
            
            shellPrint($list);
        });

        Event::wait();
    }

}
