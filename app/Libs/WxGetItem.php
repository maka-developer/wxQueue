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
        $res = $queue->request(array(),'GET',0, 0,'body');
        $uuid = substr($res, 50, 12);
        Redis::set(config('rkey.uuid.key'), $uuid);
        Redis::set(config('rkey.code.key'), 0);
    }

    /*
     *  获取url中参数
     *  type：0 正常url
     *        1 带引号和代码的url
     */
    static public function getRequest($url ,$type = 0)
    {
        if($type == 1){
            preg_match_all('#"(.*?)"#i', $url, $matches);
            $url = $matches[1][0];
        }
        return $url;
        $info = strstr($url,'?');
        $getUrl = 'https://'.$_SERVER['HTTP_HOST'].'/api/getdata'.$info;
        Redis::hset(config('rkey.errorMsg.key'), date('Y-m-d H:i:s'), $getUrl);
        $queue = new RequestHandel($getUrl);
        $res = $queue->request(array(),'GET',0, 0,'body');
        return json_decode($res,true);
    }
}