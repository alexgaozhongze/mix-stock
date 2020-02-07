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

        $redis_urls = $redis->get('urls');
        $unserialize_urls = unserialize($redis_urls);

        if (!$unserialize_urls) {
            $dates = datesHttp(30);
            $start_date = reset($dates);

            $sql = "SELECT `code`,`type`,SUM(`up`) AS `sup` FROM `hsab` WHERE `date`>='$start_date' GROUP BY `code` ORDER BY `sup` DESC";
            $list = $connection->createCommand($sql)->queryAll();

            $yestdate = $dates[29];
            $urls = [];
            foreach ($list as $value) {
                $sql = "SELECT `ema5`,`ema10`,`ema20` FROM `macd` WHERE `code`=$value[code] AND `time`>='$yestdate'";
                $macd_list = $connection->createCommand($sql)->queryAll();
            
                $macd_count = count($macd_list);
                $uper_count = 0;
                foreach ($macd_list as $macd_value) {
                    if ($macd_value['ema5'] >= $macd_value['ema20'] && $macd_value['ema10'] >= $macd_value['ema20']) {
                        $uper_count ++;
                        if ($uper_count / $macd_count >= 0.8) {
                            $market = 1 == $value['type'] ? 1 : 2;
                            $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
                            $urls[] = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
                            break;
                        }
                    }
                }
            }
            $unserialize_urls = $urls;
        }

        $urls = array_splice($unserialize_urls, 0, 5);
        $redis->setex('urls', 888, serialize($unserialize_urls));
        
        $data = [
            'list' => $urls
        ];

        return $this->render('index', $data);
    }

}
