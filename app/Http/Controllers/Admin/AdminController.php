<?php

namespace app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\WxLoading;
use App\Libs\RequestHandel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AdminController extends Controller
{
    private $TurnTime = 0;

    public function __construct()
    {
        $this->TurnTime = time() . '000';

    }
    /*
     *  1、获取uuid
     *  2、开启登录监听队列
     */
    public function index()
    {

        $url = 'https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Flogin.weixin.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_='.$this->TurnTime;
        $queue = new RequestHandel($url);
        $res = $queue->request(array(),'GET',0,array('window.QRLogin.code = 200; window.QRLogin.uuid = "'=>'','";'=>''),0,'body');
        if(strpos($res,'window.QRLogin.code = 200; window.QRLogin.uuid = "')){
            // Redis::zadd(config('rkey.resMsg.key'), $this->TurnTime, $res);           //存入message记录
            // Redis::zadd(config('rkey.uuid.key'), $this->TurnTime, substr(strstr($res, 'window.QRLogin.code = 200; window.QRLogin.uuid = "'), 50, 12));
        }else{
            echo 'error::'.$res;
            exit();
        }
        echo $res;
        //请求成功  得到uuid， 启动队列， 开始监听登录接口， 页面持续加载
        // dispatch(new WxLoading());
    }

    public function dl(Request $request)
    {
        $uuid = $request->input('uuid','');
        if($uuid == ''){
            echo '没输入uuid';
            exit();
        }
        $tip = $request->input('tip','');
        if($tip == ''){
            echo '没输入tip';
            exit();
        }
        $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=".$this->TurnTime;
        $queue = new RequestHandel($url);
        $res = $queue->request(array(),'GET',0,array('window.QRLogin.code = 200; window.QRLogin.uuid = "'=>'','";'=>''),0,'body');

        var_dump($res);
    }
}