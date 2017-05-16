<?php
/**
 *  执行请求轮询
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;

class SendRequest
{
    private $TurnTime;

    public function __construct()
    {
        $this->TurnTime = time().'000';
    }
    /*
     *  等待用户扫码登录
     *  code 0 ~ 3
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
            $res = $queue->request(array(), 'GET', 0, array('window.QRLogin.code = 200; window.QRLogin.uuid = "' => '', '";' => ''), 0, 'body');
            if (!$res) {              //无操作
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), '等待.....');
                Redis::set(config('rkey.code.key'), 0);
                exit();
            } else if (strpos($res, 'window.code=201;')) {        //通过扫码
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
                Redis::set(config('rkey.code.key'), 2);
                sleep(3);
                exit();
            } else if (strpos($res, 'window.code=200;')) {         //登录
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
                Redis::set(config('rkey.code.key'), 3);
            } else if (strpos($res, 'window.code=400;') || strpos($res, 'window.code=408;')) {     //过期
                WxGetItem::getUuid();       //重新生成uuid
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
                $errArr['url'] = $url;
                $errArr['code'] = $code;
                Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($errArr));
                Redis::set(config('rkey.code.key'), 1);
                exit();
            } else {
                Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), 'else::'.$res);
            }
        }
    }

    /*
     *  loginPage 获取用户相关数据
     */
    public function loginPage($code)
    {

    }
}