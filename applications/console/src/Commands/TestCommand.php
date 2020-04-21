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

            $sql = "SELECT `code` FROM `date_code` WHERE `date`<>CURDATE() ORDER BY `date` DESC LIMIT 5";
            $list = $connection->createCommand($sql)->queryAll();

            $code_in = implode(',', array_column($list, 'code'));
            $sql = "SELECT `code`,`date` FROM `hsab` WHERE `code` IN ($code_in) AND `date`>='2020-04-13' AND `up`>=9.9";
            $list = $connection->createCommand($sql)->queryAll();

            var_export($list);
        });
    }

}
