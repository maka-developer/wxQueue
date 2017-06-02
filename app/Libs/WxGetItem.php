<?php
/**
 *  获取微信相关信息
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;

class WxGetItem
{

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
     *  loginPage 获取用户相关数据
     */
    static public function webwxnewloginpage(&$data)
    {
        return true;
        $url = "https://".$data['host']."/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket=".$data['ticket']."&uuid=".$data['uuid']."&lang=zh_CN&scan=".$data['scan'];
        $queue = new RequestHandel($url);
        $res = $queue->request(array(), 'GET', 0, 0);
        Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),'webwxnewloginpage::',json_encode($res));
        //解析xml
        $xml = simplexml_load_string($res['body']);
        //保存值
        $data['skey'] = (string)$xml->skey;
        $data['wxsid'] = (string)$xml->wxsid;
        $data['wxuin'] = (string)$xml->wxuin;
        $data['pass_ticket'] = (string)$xml->pass_ticket;
        $data['cookie'] = (string)$xml->cookie;
        if((string)$xml->ret == '0'){
            return true;
        }else{
            return false;
        }
    }
}