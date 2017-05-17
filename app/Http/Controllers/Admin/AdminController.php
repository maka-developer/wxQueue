<?php

namespace app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\WxLoading;
use App\Libs\RequestHandel;
use App\Libs\SendRequest;
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

        // $code = Redis::get(config('rkey.code.key'));     //获取uuid
        // $sendRequest = new SendRequest();
        // $sendRequest->sendLogin($code);
        // $uuid = Redis::get(config('rkey.uuid.key'));     //获取uuid
        // $tip = 1;                                          //默认tip为1 未扫码
        // $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=" . $this->TurnTime;
        // $queue = new RequestHandel($url);
        // $res = $queue->request(array(), 'GET', 0, 0, 'body');
        // var_dump($res);
    }

    public function getdata(Request $request)
    {

        return response()->json($request);
    }

    public function dl()
    {
        $uuid = Redis::get(config('rkey.uuid.key'));       //uuid;
        $code = Redis::get(config('rkey.code.key'));       //uuid;
        $testMsgs = Redis::hGetAll(config('rkey.testMsg.key'));
        $errMsgs = Redis::hGetAll(config('rkey.errorMsg.key'));
        $queue = Redis::zRevRange('queues:default:reserved', 0, -1);
        $data = Redis::hGetAll(config('rkey.data.key'));
        $url = Redis::get(config('rkey.url.key'));

        $arr['url'] = 'https://login.weixin.qq.com/qrcode/'.$uuid;
        $arr['code'] = $code;
        $arr['msg'] = $testMsgs;
        $arr['queue'] = $queue;
        $arr['err'] = $errMsgs;
        $arr['data'] = $data;
        $arr['duUrl'] = $url;
        dd($arr);
    }

    public function test(){
        $url = Redis::hGet(config('rkey.errorMsg.key'),'2017-05-17 09:36:30');
        $res = WxGetItem::getRequest($url,1);
        dd($res);
    }

}