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

}
