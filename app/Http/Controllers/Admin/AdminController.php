<?php

namespace app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libs\WxDbUserItem;
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
        $msg = Redis::hGetAll(config('rkey.msgs.key'));
        $users = Redis::smembers(config('rkey.users.key'));
        foreach($msg as $key=>$value){
            $arr['msgs'][$key] = json_decode($value,true);
        }
        $arr['url'] = 'https://login.weixin.qq.com/qrcode/'.$uuid;
        $arr['code'] = $code;
        $arr['msg'] = $testMsgs;
        $arr['queue'] = $queue;
        $arr['err'] = $errMsgs;
        $arr['data'] = $data;
        $arr['users'] = $users;
        dd($arr);
    }

    public function test(Request $request)
    {
//        $content = $request->input('content','中文');
//        $tu = $request->input('to','');
//        if($tu == ''){
//            echo '请传入接收人';
//        }
//        WxMessage::sendMsg($tu,urlencode($content));
//        $time = $request->input('time','2017-06-22 09:53:46');
//        $msgs = Redis::hget(config('rkey.msgs.key'),$time);
//        $msgs = json_decode($msgs,true);
//        $item = WxMessage::putMessage($msgs['body']['AddMsgList']);
//        dd($item);
        $content = Redis::hget(config('rkey.msgs.key'),'2017-06-27 15:15:20');
        $content = json_decode($content, true);
        dd($content);
    }
}