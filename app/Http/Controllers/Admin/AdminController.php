<?php

namespace app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\WxLoading;
use App\Libs\RequestHandel;
use App\Libs\WxGetItem;
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

        WxGetItem::getUuid();
        //请求成功  得到uuid， 启动队列， 开始监听登录接口， 页面持续加载
        dispatch(new WxLoading());
    }

    public function dl()
    {
        $uuidKey = config('rkey.uuid.key');
        $uuid = Redis::get($uuidKey);       //uuid;
        $testMsgs = Redis::hGetAll(config('rkey.testMsg.key'));
        $errMsgs = Redis::hGetAll(config('rkey.errorMsg.key'));
        $queue = Redis::zRevRange('queues:default:reserved', 0, -1);
        $uuids = Redis::hGetAll('wx::uuids');

        $arr['url'] = 'https://login.weixin.qq.com/qrcode/'.$uuid;
        $arr['msg'] = $testMsgs;
        $arr['queue'] = $queue;
        $arr['err'] = $errMsgs;
        $arr['uuids'] = $uuids;
        dd($arr);
    }

    public function test(){
        $uuid = Redis::get(config('rkey.uuid.key'));
        $tip = 1;
        $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=".$this->TurnTime;
        echo $url;
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