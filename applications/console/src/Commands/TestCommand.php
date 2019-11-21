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
        $phones = range(1, 1000000);

        $urls = [];
        foreach ($phones as $value) {
           // $urls[] = 'http://47.103.61.179:1033/index/check-zlqb?userPhone=' . md5($value);
            $urls[] = 'https://open-api.sshua.com/index/check-zlqb?userPhone=' . md5($value);
        }

        $count = 0;

        QueryList::multiGet($urls)
        ->concurrency(150)
        ->withOptions([
            'timeout' => 3
        ])
        ->success(function(QueryList $ql, Response $response, $index) use (&$count) {
            $data = $ql->getHtml();

            $count ++;

            echo $count . ' ' . date('Y-m-d H:i:s') . ' ' . $data,PHP_EOL;
        })->error(function (QueryList $ql, $reason, $index){
            // ...
        })->send();
    }

}
