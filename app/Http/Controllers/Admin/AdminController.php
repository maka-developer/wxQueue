<?php

namespace app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\WxLoading;
use App\Libs\WxGetItem;
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

        WxGetItem::getUuid();
        //请求成功  得到uuid， 启动队列， 开始监听登录接口， 页面持续加载
        dispatch(new WxLoading());
    }

    public function dl()
    {
        $uuid = Redis::get(config('rkey.uuid.key'));       //uuid;
        $code = Redis::get(config('rkey.code.key'));       //uuid;
        $testMsgs = Redis::hGetAll(config('rkey.testMsg.key'));
        $errMsgs = Redis::hGetAll(config('rkey.errorMsg.key'));
        $queue = Redis::zRevRange('queues:default:reserved', 0, -1);

        $arr['url'] = 'https://login.weixin.qq.com/qrcode/'.$uuid;
        $arr['code'] = $code;
        $arr['msg'] = $testMsgs;
        $arr['queue'] = $queue;
        $arr['err'] = $errMsgs;
        dd($arr);
    }

    public function test(){
        $url = 'window.redirect_uri="https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket=A4zC2Cp79z8grb8jXaY7DQIp@qrticket_0&uuid=gelqxc_XmQ==&lang=zh_CN&scan=1494937733"';
        preg_match_all('#"(.*?)"#i', $url, $matches);
        $pathinfo = pathinfo($matches[1][0]);
        var_dump($pathinfo);
    }

//    public function dl(Request $request)
//    {
//        $uuid = $request->input('uuid','');
//        if($uuid == ''){
//            echo '没输入uuid';
//            exit();
//        }
//        $tip = $request->input('tip','');
//        if($tip == ''){
//            echo '没输入tip';
//            exit();
//        }
//        $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=".$this->TurnTime;
//        $queue = new RequestHandel($url);
//        $res = $queue->request(array(),'GET',0,array('window.QRLogin.code = 200; window.QRLogin.uuid = "'=>'','";'=>''),0,'body');
//
//        var_dump($res);
//    }
}