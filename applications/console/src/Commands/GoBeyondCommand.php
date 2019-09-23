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

            $sql = "select *,cjs/avg_cjs pre from (
                select a.code,a.type,round(avg(a.cjs),0) avg_cjs,b.up,b.cjs from hsab a
                left join hsab b on a.code=b.code and a.type=b.type and b.date=curdate()
                where a.date<>curdate() 
                    and LEFT(a.code,3) NOT IN (200,300,688,900) 
                    and left(a.name, 1) not in ('*', 'S') 
                    and right(a.name, 1)<>'退' 
                    and a.code not in (select code from hsab where up>=9 and date='2019-09-20')
                    group by a.code
                ) as a
            where cjs >= avg_cjs order by pre desc;";

            $list = $connection->createCommand($sql)->queryAll();


            var_dump($list);
        });

        Event::wait();
    }

}
