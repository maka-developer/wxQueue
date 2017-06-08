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
            $errArr['position'] = 'class:SendRequest=>fun:sendLogin';
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($errArr));
        }else {
            $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=" . $this->TurnTime;
            $queue = new RequestHandel($url);
            $res = $queue->request(array(), 'GET', 0, 0, 'body');
            if (!$res) {              //无操作
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), '等待.....');
                Redis::set(config('rkey.code.key'), 0);
                exit();
            } else if ($res == 'window.code=201;') {        //通过扫码
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
                Redis::set(config('rkey.code.key'), 2);
                exit();
            } else if (strstr($res,'window.code=200;')) {         //登录
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
                //保存状态值
                $code = 3;
                Redis::set(config('rkey.code.key'), $code);
                $data = GetParams::getItem($res);            //解析参数
                Redis::hmset(config('rkey.data.key'),$data);    //保存参数
                WxGetItem::loginInit($code);
                exit();
            } else if ($res == 'window.code=400;' || $res == 'window.code=408;') {     //过期
                WxGetItem::getUuid();       //重新生成uuid
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
                $errArr['url'] = $url;
                $errArr['code'] = $code;
                Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($errArr));
                Redis::set(config('rkey.code.key'), 1);
                exit();
            } else {
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
            }
        }
    }

    /*
     * 获取群组信息
     * 一次最多获取50条
     */
    public function webwxbatchgetcontact()
    {

    }
    /*
     *  同步刷新
     */
    public function synccheck()
    {
        $data = Redis::hgetall(config('rkey.data.key'));
        $url = "https://webpush.".$data['host']."/cgi-bin/mmwebwx-bin/synccheck?r=".$this->TurnTime."&skey=".$data['skey']."&sid=".$data['wxsid']."&uin=".$data['wxuin']."&deviceid=".$this->TrueRand."&synckey=".$data['syncKeyStr']."_=".time().rand(100,999);
        $queue = new RequestHandel($url);
        $res = $queue->request(array(), 'GET', $data['cookie'], 0, 'body');
        Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),$res);
        if(!strstr($res, 'retcode:"0"')){
            /*
             * 失败、退出微信
             * 1、记录error数据
             * 2、停止进程
             */
            $resArr['res'] = $res;
            $resArr['url'] = $url;
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($resArr));
            Redis::set(config('rkey.code.key'), 1101);
        }else{                                    //正常返回，查看是否有新消息 （或进入/离开聊天界面？）
            if(strstr($res, 'selector:"2"') !== false){     //有新消息
                Redis::set(config('rkey.code.key'), 102);
            } else if(strstr($res, 'selector:"7"') !== false) { //进入聊天界面  暂时使用102
                Redis::set(config('rkey.code.key'), 102);
            }
            exit();
        }
    }
}