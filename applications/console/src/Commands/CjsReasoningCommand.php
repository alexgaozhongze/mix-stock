<?php

namespace Console\Commands;

use Mix\Core\Event;

/**
 * Class CjsReasoningCommand
 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class CjsReasoningCommand
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
            if (!checkOpen()) return false;

            $connection=app()->dbPool->getConnection();
            $table_name = 'cjs_reasoning_' . date('Ymd');
            $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
                `code` mediumint(6) unsigned zerofill NOT NULL,
                `price` float(6,2) DEFAULT NULL,
                `up` float(5,2) DEFAULT NULL,
                `avg_up` float(5,2) DEFAULT NULL,
                `max_up` float(5,2) DEFAULT NULL,
                `cjs` int(10) unsigned DEFAULT NULL,
                `avg_cjs` int(10) unsigned DEFAULT NULL,
                `pre` float(4,2) DEFAULT NULL,
                `time` time NOT NULL,
                `type` tinyint(4) NOT NULL,
                PRIMARY KEY (`code`,`type`,`time`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            $connection->createCommand($sql)->execute();

            while ((strtotime('09:30') <= time() && strtotime('11:30') >= time()) || (strtotime('13:00') <= time() && strtotime('15:00') >= time())) {
                self::handle();
                usleep(888888);
            }
        });

        Event::wait();
    }

    private function handle()
    {
        $connection=app()->dbPool->getConnection();
        $dates = dates(1);
        $pre_date = reset($dates);
        $fscj_table = 'fscj_' . date('Ymd');

        $sql = "select *,round(cjs/avg_cjs, 2) pre from (
                    select a.code,a.type,round(avg(a.cjs),0) avg_cjs,b.up,round(avg(d.up), 2) avg_up,b.cjs,b.price,max(d.up) as max_up from hsab a
                    left join hsab b on a.code=b.code and a.type=b.type and b.date=curdate()
                    left join $fscj_table d on a.code=d.code and a.type=d.type
                    where a.date<>curdate() 
                        and LEFT(a.code,3) NOT IN (200,300,688,900) 
                        and left(a.name, 1) not in ('*', 'S') 
                        and right(a.name, 1)<>'退' 
                        and a.code not in (select code from hsab where up>=9 and date='$pre_date')
                        group by a.code
                    ) as a
                where cjs >= avg_cjs and avg_up>=0 order by pre desc;";

        $list = $connection->createCommand($sql)->queryAll();
        
        $reasoning_table = 'cjs_reasoning_' . date('Ymd');
        $time = date('H:i:s');
        $sql_fields = "INSERT INTO `$reasoning_table` (`code`, `price`, `up`, `avg_up`, `max_up`, `cjs`, `avg_cjs`, `pre`, `time`, `type`) VALUES ";
        $sql_values = "";

        foreach ($list as $value) {
            $sql_values && $sql_values .= ',';
            $sql_values .= "($value[code], $value[price], $value[up], $value[avg_up], $value[max_up], $value[cjs], $value[avg_cjs], $value[pre], '$time', $value[type])";
        }

        $sql_duplicate = " ON DUPLICATE KEY UPDATE `price`=VALUES(`price`), `up`=VALUES(`up`), `avg_up`=VALUES(`avg_up`), `max_up`=VALUES(`max_up`), `cjs`=VALUES(`cjs`), `avg_cjs`=VALUES(`avg_cjs`), `pre`=VALUES(`pre`);";

        $sql = $sql_fields . $sql_values . $sql_duplicate;
        $connection->createCommand($sql)->execute();
    }

}
