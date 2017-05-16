<?php
/**
 *  执行请求轮询
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;

class SendRequest
{
    private $TurnTime;
    private $testMsgKey;
    private $uuidKey;

    public function __construct()
    {
        $this->TurnTime = time().'000';
        $this->testMsgKey = config('rkey.testMsg.key');
        $this->uuidKey = config('rkey.uuid.key');
    }

    public function sendLogin($tip)
    {
        $uuid = Redis::get($this->uuidKey);
        $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=".$this->TurnTime;
        $queue = new RequestHandel($url);
        $res = $queue->request(array(),'GET',0,array('window.QRLogin.code = 200; window.QRLogin.uuid = "'=>'','";'=>''),0,'body');
        if(!$res){              //无操作
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), '等待.....');
            $this->sendLogin(1);
        }else if(strpos($res, 'window.code=201;')){        //通过扫码
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), $res);
            sleep(8);
            $this->sendLogin(0);
        }else if(strpos($res, 'window.code=200;')){
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), $res);
            exit();
        }else{
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), $res);
            exit();
        }
    }

}