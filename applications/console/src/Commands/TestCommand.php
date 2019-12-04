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
        $phones = range(1, 10000);

        $urls = [];
        foreach ($phones as $value) {
        //    $urls[] = 'http://47.103.61.179:1033/index/check-zlqb?userPhone=' . md5($value);
            $mobile = rand(10000000000, 99999999999);
            $urls[] = 'http://139.196.232.14:8013/user/get-access-token?mobile=$mobile&status=1';
            $urls[] = 'http://139.196.232.14:8013/user/auto-login';
            $urls[] = 'http://139.196.232.14:8013/user/auto-login';
            $urls[] = 'http://139.196.232.14:8013/user/auto-login';

            // $urls[] = 'https://www.xiucai.com/class/detail/1028/';
        }

        $count = 0;

        QueryList::multiGet($urls)
        ->concurrency(200)
        ->withOptions([
            'timeout' => 3
        ])
        ->success(function(QueryList $ql, Response $response, $index) use (&$count) {
            $data = $ql->getHtml();

            $count ++;

            // echo $count,PHP_EOL;
            echo $count . ' ' . date('Y-m-d H:i:s') . ' ' . $data,PHP_EOL;
        })->error(function (QueryList $ql, $reason, $index){
            // ...
        })->send();
    }

}
