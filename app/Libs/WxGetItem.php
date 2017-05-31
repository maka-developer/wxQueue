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

    static public function updateSyncKey($arr)
    {
        if($arr === false || empty($arr)){
            return false;
        }
        $count = $arr['Count'];
        $resStr = '';
        for($i=0; $i<$count; $i++){
            $listArr = $arr['List'][$i];
            if($i == $count - 1){
                $resStr .= $listArr['Key'].'_'.$listArr['Val'];
            }else{
                $resStr .= $listArr['Key'].'_'.$listArr['Val'].'|';
            }
        }
        return $resStr;
    }
}