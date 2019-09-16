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

        $fscj_table = 'fscj_' . date('Ymd');

        $sql = "SELECT `a`.`code`,`a`.`price`,`b`.`price` AS `nprice`,`a`.`up`,`b`.`up` AS `nup`,`a`.`num`,`a`.`time` FROM `$fscj_table` AS `a` LEFT JOIN `hsab` AS `b` ON `a`.`code`=`b`.`code` AND `b`.`date`=CURDATE() WHERE `a`.`bs`=2 AND `a`.`ud`=1 AND `a`.`up`<=5 AND `a`.`time`<='09:30:59' AND `a`.`time`>='09:30:00' ORDER BY `a`.`num` DESC LIMIT 88";
        $zjlx_list = $connection->createCommand($sql)->queryAll();

        $data = [
            'list' => $zjlx_list,
        ];
        return $this->render('index', $data);
    }

    public function actionIndexNew(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        $connection=app()->db;

        $a = GoBeyond::abc();

        $fscj_table = 'fscj_' . date('Ymd');

        $sql = "select code from hsab where date=curdate() and up>=9.9";
        $code_list = $connection->createCommand($sql)->queryAll();

        var_dump($code_list);

        // return $this->render('index', $data);
    }

}
