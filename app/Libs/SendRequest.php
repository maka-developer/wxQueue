<?php
/**
 *  执行请求轮询
 */
namespace App\Libs;

use App\Libs\GetParams;
use Illuminate\Support\Facades\Redis;

class SendRequest
{
    private $TurnTime;
    private $TrueRand;

    public function __construct()
    {
        $this->TurnTime = time().'000';
        $this->TrueRand = 'e'.time().rand(10000,99999);
    }
    /*
     *  等待用户扫码登录
     *  code 0 ~ 2
     */
    public function sendLogin($code)
    {
        $uuid = Redis::get(config('rkey.uuid.key'));     //获取uuid
        $tip = 1;                                          //默认tip为1 未扫码
        switch($code)
        {
            case 0:
            case 1:
                $tip = 1;
                break;
            case 2:
                $tip = 0;
                break;
            default :
                $uuid = 'error';
                break;
        }

        if($uuid == 'error'){
            $errArr['code'] = $code;
            $errArr['msg'] = '未获取到openid';
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($errArr));
        }else {
            $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=" . $this->TurnTime;
            $queue = new RequestHandel($url);
            $res = $queue->request(array(), 'GET', 0, 0, 'body');
            if (!$res) {              //无操作
                Redis::set(config('rkey.code.key'), 0);
                exit();
            } else if ($res == 'window.code=201;') {        //通过扫码
                Redis::set(config('rkey.code.key'), 2);
                exit();
            } else if (strstr($res,'window.code=200;')) {         //登录
                //保存状态值
                $code = 3;
                Redis::set(config('rkey.code.key'), $code);
                $data = GetParams::getItem($res);            //解析参数
                Redis::hmset(config('rkey.data.key'),$data);    //保存参数
                WxGetItem::loginInit($code);
                exit();
            } else if ($res == 'window.code=400;' || $res == 'window.code=408;') {     //过期
                WxGetItem::getUuid();       //重新生成uuid
                $errArr['msg'] = 'uuid过期';
                $errArr['url'] = $url;
                $errArr['code'] = $code;
                Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($errArr));
                Redis::set(config('rkey.code.key'), 1);
                exit();
            } else {
                $resArr['msg'] = '未知错误';
                $resArr['result'] = $res;
                Redis::hset(config('rkey.errorMsg.key'), date('Y-m-d H:i:s'), json_encode($resArr));
                $code = 1120;
                Redis::set(config('rkey.code.key'), $code);
            }
        }
    }
    /*
     *  同步刷新
     */
    public function synccheck()
    {
        $data = Redis::hgetall(config('rkey.data.key'));
        if(array_key_exists('_',$data)){
            $_ = GetParams::numAdd($data['_']);
            Redis::hset(config('rkey.data.key'), '_', $_);
        }else{
            $_ = (time() - 2*60*60) . '000';
            Redis::hset(config('rkey.data.key'), '_', $_);
        }
        $url = "https://webpush.".$data['host']."/cgi-bin/mmwebwx-bin/synccheck?skey=".urlencode($data['skey'])."&sid=".urlencode($data['wxsid'])."&uin=".$data['wxuin']."&deviceid=".$this->TrueRand."&synckey=".urlencode(GetParams::updateSyncKey($data['syncKey']))."&_=$_";
        $queue = new RequestHandel($url);
        $res = $queue->request(array(), 'GET', $data['cookie'], 0, 'body');
        $resArr['res'] = $res;
        $resArr['url'] = $url;
        Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),json_encode($resArr));
        if(!strstr($res, 'retcode:"0"')){
            if(!$res){
                //没有消息体的时候会返回false，不知道为什么
                exit();
            }else{
                $resArr['res'] = $res;
                $resArr['url'] = $url;
                Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($resArr));
                Redis::set(config('rkey.code.key'), 1101);
            }
        }else{                                    //正常返回，查看是否有新消息 （或进入/离开聊天界面？）
            $selector = $res;
            WxMessage::getMessage($selector);
//            if(strstr($res, 'selector:"2"') !== false){     //有新消息
//                Redis::set(config('rkey.code.key'), 102);
//            } else if(strstr($res, 'selector:"7"') !== false) { //进入聊天界面  暂时使用102
//                Redis::set(config('rkey.code.key'), 102);
//            }
            exit();
        }
    }
}