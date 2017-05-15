<?php
/**
 *  获取微信相关信息
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;

class WxGetItem
{

    public function __construct()
    {

    }
    //获取uuid并存入redis
    static public function getUuid()
    {
        $TurnTime = time() . '000';
        $url = 'https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Flogin.weixin.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_='.$TurnTime;
        $queue = new RequestHandel($url);
        $res = $queue->request(array(),'GET',0,array('window.QRLogin.code = 200; window.QRLogin.uuid = "'=>'','";'=>''),0,'body');
        if(strpos($res,'window.QRLogin.code = 200; window.QRLogin.uuid = "')){
//             Redis::zadd(config('rkey.resMsg.key'), time(), strstr($res, 'window.QRLogin.code = 200; window.QRLogin.uuid = "'));           //存入message记录
            Redis::set(config('rkey.uuid.key'), substr(strstr($res, 'window.QRLogin.code = 200; window.QRLogin.uuid = "'), 50, 12));
        }else{
            Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
        }
    }
}