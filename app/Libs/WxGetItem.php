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
        $url = 'https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Flogin.weixin.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_=' . $TurnTime;
        $queue = new RequestHandel($url);
        $res = $queue->request(array(), 'GET', 0, 0, 'body');
        $uuid = substr($res, 50, 12);
        Redis::set(config('rkey.uuid.key'), $uuid);
        Redis::set(config('rkey.code.key'), 0);
    }

    /*
     * 防止一次队列不能完全执行的任务操作
     */
    static public function loginInit($code, $data=array())
    {
        if(empty($data)){
            $data = Redis::hgetall(config('rkey.data.key'));
        }
        if($code == 3){
            if(!self::webwxnewloginpage($data,$code)){
                Redis::set(config('rkey.code.key'), 1101);
                exit();
            }
            Redis::set(config('rkey.code.key'), $code);
        }
        if($code == 4){
            if(!self::webwxinit($data,$code)){
                Redis::set(config('rkey.code.key'), 1101);
                exit();
            }
            Redis::set(config('rkey.code.key'), $code);
        }
        if($code == 5){
            self::webwxstatusnotify($data,$code);
            Redis::set(config('rkey.code.key'), 1101);
        }
    }

    /*
     *  loginPage 获取用户相关数据
     */
    static public function webwxnewloginpage(&$data,&$code)
    {
        $url = "https://".$data['host']."/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket=".$data['ticket']."&uuid=".$data['uuid']."&lang=zh_CN&scan=".$data['scan'];
        $queue = new RequestHandel($url);
        $res = $queue->request(array(), 'GET', 0, 0);
        Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),json_encode($res));
        Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),$url);
        //解析xml
        $xml = simplexml_load_string($res['body']);
        //保存值
        if((string)$xml->ret == '0'){
            $data['skey'] = (string)$xml->skey;
            $data['wxsid'] = (string)$xml->wxsid;
            $data['wxuin'] = (string)$xml->wxuin;
            $data['pass_ticket'] = (string)$xml->pass_ticket;
            $data['cookie'] = $res['cookie'];
            Redis::hmset(config('rkey.data.key'),$data);    //保存参数
            $code = 4;
            return true;
        }else{
            return false;
        }
    }
    /*
     * 微信初始化
     * 1、获取用户相关信息
     * 2、获取synckey除版
     */
    public function webwxinit(&$data,&$code)
    {
        $deviceId = 'e'.time().rand(10000,99999);
        $url = "https://".$data['host']."/cgi-bin/mmwebwx-bin/webwxinit?r=-".time()."&pass_ticket=".$data['pass_ticket']."&lang=zh_CN";
        $queue = new RequestHandel($url);
        $post = [
            'BaseRequest' => [
                'Uin' => $data['wxuin'],
                'Sid' => $data['wxsid'],
                'Skey' => $data['skey'],
                'DeviceID' => $deviceId
            ]
        ];
        $res = $queue->request($post, 'POST', '', 1);
        if($res['body']['User']['Uin'] == $data['wxuin']){      //获取正确数据
            $data['UserName'] = (string) $res['body']['User']['UserName'];
            $data['syncKey'] = GetParams::updateSyncKey($res['body']['SyncKey']);
            Redis::hmset(config('rkey.data.key'),$data);    //保存参数
            $code = 5;
            //保存synckey
            Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),json_encode($res));
            return true;
        }else{      //获取错误数据
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($res));
            return false;
        }
    }

    /*
     * 开启微信状态通知
     */
    static public function webwxstatusnotify(&$data,&$code)
    {
        $deviceId = 'e'.time().rand(10000,99999);
        $ClientMsgId = time().'000';
        $url = "https://".$data['host']."/cgi-bin/mmwebwx-bin/webwxstatusnotify?pass_ticket=".$data['pass_ticket'];
        $queue = new RequestHandel($url);
        $post = [
            'BaseRequest' => [
                'Uin' => $data['wxuin'],
                'Sid' => $data['wxsid'],
                'Skey' => $data['skey'],
                'DeviceID' => $deviceId
            ],
            'ClientMsgId'=>$ClientMsgId,
            'Code' => 3,
            'FromUserName' => $data['UserName'],
            'ToUserName' => $data['UserName']
        ];
        $res = $queue->request($post, 'POST', '', 1, 'body');
        $code = 6;
        Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),json_encode($res));
        exit();
    }
    /*
     *获取联系人信息
     * 1、处理联系人列表
     */
    public function webwxgetcontact()
    {
        $pass_ticket = Redis::hget(config('rkey.data.key'),'pass_ticket');
        $host = Redis::hget(config('rkey.data.key'),'host');
        $skey = Redis::hget(config('rkey.data.key'),'skey');
        $cookie = Redis::hget(config('rkey.data.key'),'cookie');
        $url = "https://$host/cgi-bin/mmwebwx-bin/webwxgetcontact?r=".$this->TurnTime."&seq=0&skey=$skey&pass_ticket=$pass_ticket&lang=zh_CN";
        $queue = new RequestHandel($url);
        $res = $queue->request(array(), 'POST', $cookie, 1);
        Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),json_encode($res));
        if($res['body']['BaseResponse']['Ret'] == 0){
            Redis::set(config('rkey.code.key'), 7);
        }
        exit();
    }
}