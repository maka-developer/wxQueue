<?php

namespace app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\WxLoading;
use App\Libs\GetParams;
use App\Libs\RecordLog;
use App\Libs\RequestHandel;
use App\Libs\SendRequest;
use App\Libs\WxGetItem;
use App\Libs\WxMessage;
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
        $log = Redis::hGetAll(config('rkey.log.key'));
        $msg = Redis::hGetAll(config('rkey.msgs.key'));
        foreach($log as $key=>$value){
            $arr['log'][$key] = json_decode($value,true);
        }
        foreach($msg as $key=>$value){
            $arr['msgs'][$key] = json_decode($value,true);
        }

        $arr['url'] = 'https://login.weixin.qq.com/qrcode/'.$uuid;
        $arr['code'] = $code;
        $arr['msg'] = $testMsgs;
        $arr['queue'] = $queue;
        $arr['err'] = $errMsgs;
        $arr['data'] = $data;
        dd($arr);
    }

    public function test()
    {
//        $data = 'window.code=200;\n
//      window.redirect_uri="https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket=A7aMxW7XyhVkEgiBTcO4ARlS@qrticket_0&uuid=YdCo10e3zg==&lang=zh_CN&scan=1495029818"; ◀
//      "';
//        $res = GetInput::getWebWxNewLoginPage($data);
//        dd($res);
         //xml
//        $arr = json_decode(Redis::hget(config('rkey.testMsg.key'),'2017-05-30 08:59:56'),true);
//        $arr['xml'] = simplexml_load_string($arr['body']);
//        $arr['message'] = (string)$arr['xml']->pass_ticket;

//        $arr['initstr'] = Redis::hget(config('rkey.testMsg.key'),'2017-06-02 10:09:23');
//        $arr['contectstr'] = Redis::hget(config('rkey.testMsg.key'),'2017-06-02 10:09:36');
//        $arr['init'] = json_decode($arr['initstr'],true);
//        $arr['contect'] = json_decode($arr['contectstr'],true);
        //redis hmset 测试
//        $arr['res'] = Redis::hget(config('rkey.testMsg.key'),'2017-06-02 16:19:17');
//        $arr['data'] = GetParams::getItem($arr['res']);            //解析参数
//        $arr['bool'] = WxGetItem::webwxnewloginpage($arr['data']);
//        WxMessage::getMessage();
//        dd($arr);
        //更改cookie
//        $res = Redis::hget(config('rkey.log.key'),'msg2017-06-08 17:38:55');
//        $res = json_decode($res,true);
//        $cookie = $res['res']['cookie'];
//        $resArr = GetParams::mergeCookie($cookie);
//        $resArr = Redis::hgetall(config('rkey.data.key'));
        RecordLog::log('通过扫码',1);
    }
}