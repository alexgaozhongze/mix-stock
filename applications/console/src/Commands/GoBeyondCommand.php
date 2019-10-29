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

            $sql = "select code,zf from hsab 
                        where date='2019-09-19'
                            and LEFT(code,3) NOT IN (200,300,688,900) 
                            and left(name, 1) not in ('*', 'S') 
                            and right(name, 1)<>'退' 
                            and code not in (select code from hsab where up>=9 and date='2019-09-18') group by code";
            $list = $connection->createCommand($sql)->queryAll();

            foreach ($list as $value) {
                $sql = "select sum(num) as snum from fscj_20190919 where code=810 and time>='14:30:00' and time<='15:00:00' and bs=1";
                $sql = "select avg(num) as a_num from fscj_20190919 where code=$value[code]";
                $info = $connection->createCommand($sql)->queryOne();
                $avg_num = $info['a_num'];

                $sql = "select s1_n as num from hqbj_20190919 where code=$value[code] and time<='15:00:00' order by time desc";
                $info = $connection->createCommand($sql)->queryOne();
                $num = $info['num'];

                $sql = "select up,zf from hsab where code=$value[code] and date='2019-09-20'";
                $info = $connection->createCommand($sql)->queryOne();
                $up = $info['up'];
                $zf = $info['up'];

                echo str_pad($value['code'], 8)
                ,str_pad($value['zf'], 8)
                ,str_pad($zf, 8)
                ,str_pad(intval($avg_num), 8)
                ,str_pad($num, 8)
                ,str_pad($up, 8)
                ,PHP_EOL;
            }

            // var_dump($list);
            
            // shellPrint($listZ);
        });

        Event::wait();
    }

}
