<?php

namespace Console\Commands;

use QL\QueryList;
use GuzzleHttp\Psr7\Response;
use Mix\Console\CommandLine\Flag;

/**
 * Class class MacdSyncCommand

 * @package Console\Commands
 * @author alex <alexgaozhongze@gmail.com>
 */
class MacdSyncCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        xgo(function () {
            $code = Flag::string('code');

            $connection=app()->dbPool->getConnection();
            $sql = "SELECT `code`,`type` FROM `macd` WHERE `time`>=CURDATE()";
            $code && $sql .= " AND `code`=$code";
            $sql .= " GROUP BY `code`";
            $code_list = $connection->createCommand($sql)->queryAll();

            $code_type_list = array_column($code_list, 'type', 'code');
            $code_list = array_column($code_list, 'code');

            foreach ($code_list as $value) {
                $sql = "SELECT `sp`,`time` FROM `macd` WHERE `code`=$value AND `type`=$code_type_list[$value] AND `time`>=CURDATE()";
                $list = $connection->createCommand($sql)->queryAll();

                $min_time = reset($list)['time'];
                $sql = "SELECT `ema12`,`ema26`,`dea` FROM `macd` WHERE `code`=$value AND `type`=$code_type_list[$value] AND `time`<'$min_time' ORDER BY `time` DESC";
                $info = $connection->createCommand($sql)->queryOne();

                $pre_l_value = $info ?: [];
                foreach ($list as $l_value) {
                    $sp = $l_value['sp'];

                    if ($pre_l_value) {
                        $pre_ema12 = $pre_l_value['ema12'];
                        $pre_ema26 = $pre_l_value['ema26'];
                        $pre_dea = $pre_l_value['dea'];
                    } else {
                        $pre_ema12 = $pre_ema26 = $l_value['sp'];
                        $pre_dea = 0;
                    }

                    $ema12 = 2 / (12 + 1) * $sp + (12 - 1) / (12 + 1) * $pre_ema12;
                    $ema26 = 2 / (26 + 1) * $sp + (26 - 1) / (26 + 1) * $pre_ema26;

                    $dif = $ema12 - $ema26;
                    $dea = 2 / (9 + 1) * $dif + (9 - 1) / (9 + 1) * $pre_dea;
                    $macd = 2 * ($dif - $dea);

                    $l_value['dif'] = round($dif, 3);
                    $l_value['dea'] = round($dea, 3);
                    $l_value['macd'] = round($macd, 3);
                    $l_value['ema12'] = round($ema12, 3);
                    $l_value['ema26'] = round($ema26, 3);
                
                    $sql = "UPDATE `macd` SET `dif`=$l_value[dif], `dea`=$l_value[dea], `macd`=$l_value[macd], `ema12`=$l_value[ema12], `ema26`=$l_value[ema26] WHERE `code`=$value AND `time`='$l_value[time]'";
                    
                    $connection->createCommand($sql)->execute();

                    echo $sql,PHP_EOL;

                    $pre_l_value = $l_value;
                }
            }

        });
    }

}
