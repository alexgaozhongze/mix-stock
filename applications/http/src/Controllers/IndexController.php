<?php

namespace Http\Controllers;

use Http\Libraries\GoBeyond;
use Mix\Http\Message\Request\HttpRequestInterface;
use Mix\Http\Message\Response\HttpResponseInterface;
use Mix\Http\View\ViewTrait;

/**
 * Class IndexController
 * @package Http\Controllers
 * @author alex <alexgaozhongze@gmail.com>
 */
class IndexController
{

    /**
     * 引用视图特性
     */
    use ViewTrait;

    /**
     * 默认动作
     * @return string
     */
    public function actionIndex(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        $connection=app()->db;
        $redis = app()->redis;

        $dates = datesHttp(30);
        $start_date = reset($dates);

        $sql = "SELECT `code`,SUM(`up`) AS `sup` FROM `hsab` WHERE `date`>='$start_date' GROUP BY `code` ORDER BY `sup` DESC";
        $list = $connection->createCommand($sql)->queryAll();

        $sort_code = implode(',', array_column($list, 'code'));
        $upstop_start_date = $dates[23];
        $upstop_end_date = $dates[29];

        $sql = "SELECT `code`,`type` FROM `hsab` WHERE `date`>='$upstop_start_date' AND `date`<='$upstop_end_date' AND `up`>=9.9 GROUP BY `code` ORDER BY FIELD(`code`, $sort_code)";
        $list = $connection->createCommand($sql)->queryAll();

        $count = count($list);
        $step = 5;

        $index = $redis->get('index');
        (!$index || $index >= $count - $step) && $redis->setex('index', 3600, 0) && $index = 0;
        $index_end = $index + $step;

        $urls = [];
        foreach ($list as $key => $value) {
            if ($key >= $index && $key < $index_end) {
                $type = 1 == $value['type'] ? 'sh' : 'sz';
                $market = 1 == $value['type'] ? 1 : 2;
                $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
                $urls[] = "http://quote.eastmoney.com/concept/$type$code.html#fschart-m5k";
                // $urls[] = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
            }
        }
        
        $redis->setex('index', 3600, $index_end);
        
        $data = [
            'list' => $urls
        ];

        return $this->render('index', $data);
    }

}
