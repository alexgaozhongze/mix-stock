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
        $dates = GoBeyond::dates(1);
        $pre_date = reset($dates);

        $sql = "select *,round(cjs/avg_cjs, 2) pre from (
                    select a.code,a.type,round(avg(a.cjs),0) avg_cjs,b.up,round(avg(d.up), 2) avg_up,b.cjs,b.price from hsab a
                    left join hsab b on a.code=b.code and a.type=b.type and b.date=curdate()
                    left join fscj_20190923 d on a.code=d.code and a.type=d.type
                    where a.date<>curdate() 
                        and LEFT(a.code,3) NOT IN (200,300,688,900) 
                        and left(a.name, 1) not in ('*', 'S') 
                        and right(a.name, 1)<>'退' 
                        and a.code not in (select code from hsab where up>=9 and date='$pre_date')
                        group by a.code
                    ) as a
                where cjs >= avg_cjs and avg_up>=0 order by pre desc;";

        $list = $connection->createCommand($sql)->queryAll();
        
        $data = [
            'list' => $list
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
