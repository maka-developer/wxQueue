<?php
/**
 *  执行请求轮询
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;
use App\Libs\WxGetItem;

class SendRequest
{
    private $TurnTime;

    public function __construct()
    {
        $this->TurnTime = time().'000';
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
                sleep(2);
                exit();
            } else if (strstr($res,'window.code=200;')) {         //登录
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
                Redis::set(config('rkey.code.key'), 3);
                $data = GetInput::getItem($res);            //解析参数
                Redis::hset(config('rkey.data.key'),'ticket',$data['ticket']);
                Redis::hset(config('rkey.data.key'),'scan',$data['scan']);
                Redis::set(config('rkey.uuid.key'), $data['uuid']);
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
     *  loginPage 获取用户相关数据
     */
    public function webwxnewloginpage()
    {
        $ticket = Redis::hget(config('rkey.data.key'),'ticket');
        $scan = Redis::hget(config('rkey.data.key'),'scan');
        $uuid = Redis::get(config('rkey.uuid.key'));       //uuid;
        $url = "https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket=$ticket&uuid=$uuid&lang=zh_CN&scan=$scan";
        $queue = new RequestHandel($url);
        $res = $queue->request(array(), 'GET', 0, 0);
        Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),json_encode($res));
        //解析xml
        $xml = simplexml_load_string($res['body']);
        //保存值
        Redis::hset(config('rkey.data.key'),'skey', (string)$xml->skey);
        Redis::hset(config('rkey.data.key'),'wxsid',(string)$xml->wxsid);
        Redis::hset(config('rkey.data.key'),'wxuin',(string)$xml->wxuin);
        Redis::hset(config('rkey.data.key'),'pass_ticket',(string)$xml->pass_ticket);
        Redis::hset(config('rkey.data.key'),'cookie',$res['cookie']);
        Redis::set(config('rkey.code.key'), 4);
        exit();
    }
    /*
     * 微信初始化
     */
    public function webwxinit()
    {
        $pass_ticket = Redis::hget(config('rkey.data.key'),'pass_ticket');
        $skey = Redis::hget(config('rkey.data.key'),'skey');

    }
}