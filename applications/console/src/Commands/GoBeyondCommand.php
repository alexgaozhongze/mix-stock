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
            $dates = dates(1);
            $pre_date = reset($dates);

            $sql = "select *,round(cjs/avg_cjs, 2) pre from (
                        select a.code,a.type,round(avg(a.cjs),0) avg_cjs,b.up,b.cjs,b.jk,b.price from hsab a
                        left join hsab b on a.code=b.code and a.type=b.type and b.date=curdate()
                        where a.date<>curdate() 
                            and LEFT(a.code,3) NOT IN (200,300,688,900) 
                            and left(a.name, 1) not in ('*', 'S') 
                            and right(a.name, 1)<>'退' 
                            and a.code not in (select code from hsab where up>=9 and date='$pre_date')
                            group by a.code
                        ) as a
                    where cjs >= avg_cjs order by pre desc;";

            $list = $connection->createCommand($sql)->queryAll();
            
            shellPrint($list);
        });

        Event::wait();
    }

}
