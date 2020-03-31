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

        $sql = "SELECT * FROM `date_code` ORDER BY `date` DESC LIMIT 1";
        $date_code = $connection->createCommand($sql)->queryOne();

        $codes = $date_code['code'];

        $sql = "SELECT `code`,`type` FROM `hsab` WHERE `code` IN ($codes) AND `date`=CURDATE() ORDER BY `up` LIMIT 0,8";
        $list = $connection->createCommand($sql)->queryAll();

        $urls = [];
        foreach ($list as $value) {
            $market = 1 == $value['type'] ? 1 : 2;
            $code = str_pad($value['code'], 6, '0', STR_PAD_LEFT);
            $urls[] = "http://quote.eastmoney.com/basic/h5chart-iframe.html?code=$code&market=$market&type=m5k";
        }
        
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
