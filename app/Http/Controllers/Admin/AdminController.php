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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AdminController extends Controller
{
    private $TurnTime = 0;

    public function __construct()
    {
        $this->TurnTime = time() . '000';

    }

    /**
     * 初始化微信操作
     * @param Request $request
     * @return string
     */
    public function index(Request $request)
    {
        $token = $request->input('token','');
        $act = $request->input('act','');
        $tokArr = Redis::get(config('rkey.WxState.key'));
        $tokArr = json_decode($tokArr, true);

        if(is_array($tokArr)&&array_key_exists('time', $tokArr)){
            $time = $tokArr['time'];
            if((time() - $time) > 60){
                $resArr['code'] = -1;
                $resArr['msg'] = 'REQUEST ERROR';
                return json_encode($resArr);
            }
            if($tokArr['token'] != $token){
                $resArr['code'] = -3;
                $resArr['msg'] = 'TOKEN ERROR';
                return json_encode($resArr);
            }
        }else{
            $resArr['code'] = -2;
            $resArr['msg'] = 'TOKEN ERROR';
            return json_encode($resArr);
        }

        if($act == 'reload'){
            $uuid = WxGetItem::getUuid();
            //请求成功  得到uuid， 启动队列， 开始监听登录接口， 页面持续加载
//            dispatch(new WxLoading());
            DB::table('groups')->truncate();
            $resArr['code'] = 0;
            $resArr['url'] = 'https://login.weixin.qq.com/qrcode/'.$uuid;
            return json_encode($resArr);
        }else{
            $code = Redis::get(config('rkey.code.key'));
            if($code == 0 || $code == 1101){    //停止状态
                $uuid = WxGetItem::getUuid();
                //请求成功  得到uuid， 启动队列， 开始监听登录接口， 页面持续加载
//            dispatch(new WxLoading());
                DB::table('groups')->truncate();
                $resArr['code'] = 0;
                $resArr['url'] = 'https://login.weixin.qq.com/qrcode/'.$uuid;
                return json_encode($resArr);
            }else{
                $resArr['code'] = -4;
                $resArr['msg'] = 'WORKED NOW';
                return json_encode($resArr);
            }
        }
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
        $content = Redis::hget(config('rkey.msgs.key'),'2017-06-27 15:36:25');
        $content = json_decode($content, true);
        WxMessage::putMessage($content['body']);
        dd($content);
    }
}