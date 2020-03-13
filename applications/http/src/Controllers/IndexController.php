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

        $dates = datesHttp(8);
        $start_date = reset($dates);

        $sql = "SELECT `code`,SUM(`up`) AS `sup` FROM `hsab` WHERE `date`>='$start_date' GROUP BY `code` ORDER BY `sup` DESC";
        $list = $connection->createCommand($sql)->queryAll();

        $sort_code = implode(',', array_column($list, 'code'));
        $end_date = end($dates);
        
        $sql = "SELECT `code`,`type` FROM `hsab` WHERE LEFT(`code`,3) NOT IN (300,688) AND `date`>='$start_date' AND `date`<'$end_date' AND `up`>=9.9 GROUP BY `code` ORDER BY FIELD(`code`, $sort_code)";
        $list = $connection->createCommand($sql)->queryAll();

        $count = count($list);
        $step = 8;

        $index = $redis->get('index');
        (!$index || $index >= $count - $step) && $redis->setex('index', 888, 0) && $index = 0;
        $index_end = $index + $step;

        echo $count, PHP_EOL;
        $urls = [];
        foreach ($list as $key => $value) {
            if ($key >= $index && $key < $index_end) {
                $market = 1 == $value['type'] ? 1 : 2;
                $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
                $urls[] = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
                echo $key, PHP_EOL;
            }
        }
        
        $redis->setex('index', 888, $index_end);
        
        $data = [
            'list' => $urls
        ];

        return $this->render('index', $data);
    }

    public function actionToday(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        $connection=app()->db;

        $sql = "SELECT `code`,`type` FROM `hsab` WHERE `date`=CURDATE() AND `up`>=9.9 limit 0,10";
        $list = $connection->createCommand($sql)->queryAll();

        $urls = [];
        foreach ($list as $key => $value) {
            $market = 1 == $value['type'] ? 1 : 2;
            $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
            $urls[] = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
        }
        
        $data = [
            'list' => $urls
        ];

        return $this->render('index', $data);
    }

    public function actionGo(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        $connection=app()->db;
        $dates = datesHttp(8);

        $sql = "SELECT `code`,`type` FROM `hsab` WHERE `date`=CURDATE() AND `price` IS NOT NULL AND LEFT(`code`,3) NOT IN (200,300,688,900) AND LEFT(`name`, 1) NOT IN ('*', 'S') AND RIGHT(`name`, 1)<>'退'";
        $code_list = $connection->createCommand($sql)->queryAll();

        $urls = [];
        foreach ($code_list as $value) {
            $i = 0;
            foreach ($dates as $date) {
                $sql = "SELECT MIN(`zd`) AS `kp` FROM `macd` WHERE `code`=$value[code] AND `time`='$date 09:35:00'";
                $info = $connection->createCommand($sql)->queryOne();
                $kp = $info['kp'];

                $sql = "SELECT `sp` FROM `macd` WHERE `code`=$value[code] AND `time`='$date 15:00:00'";
                $info = $connection->createCommand($sql)->queryOne();
                $sp = $info['sp'];
                
                if ($sp > $kp) {
                    $i ++;
                } else {
                    break;
                }
            }

            if ($i == count($dates)) {
                $market = 1 == $value['type'] ? 1 : 2;
                $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
                $urls[] = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
            }
        }

        $data = [
            'list' => $urls
        ];

        return $this->render('index', $data);
    }

}
