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
            $uuidArr['uuid'] = substr(strstr($res, 'window.QRLogin.code = 200; window.QRLogin.uuid = "'), 50, 12);
            $uuidArr['code'] = 0;
            Redis::set(config('rkey.uuid.key'), json_encode($uuidArr));
            Redis::hset('wx::uuids', date('Y-m-d H:i:s'), $uuidArr['uuid']);
        }else{
            Redis::hset(config('rkey.testMsg.key'), date('Y-m-d H:i:s'), $res);
            return 'error';
        }
    }
}