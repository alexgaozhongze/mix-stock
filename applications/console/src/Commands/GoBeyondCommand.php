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
            $dates = dates(10);

            $sql = "select code,type from hsab where date=curdate() and up>=9.9";
            $hsab_list = $connection->createCommand($sql)->queryAll();

            foreach ($hsab_list as $hsab_value) {
                foreach ($dates as $dates_value) {
                    $fscj_table = 'fscj_' . date('Ymd', strtotime($dates_value));
                    $sql = "SELECT * FROM `$fscj_table` WHERE `code`=$hsab_value[code] AND `type`=$hsab_value[type] AND `time`<='09:30:03'";
                    $fscj_list = $connection->createCommand($sql)->queryAll();
                    echo $hsab_value['code'], '   ', $dates_value, PHP_EOL;
                    shellPrint($fscj_list);
                }
            }
        });

        Event::wait();
    }

}
