<?php
/**
 *  执行请求轮询
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;
use League\Flysystem\Exception;

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

    public function sendLogin()
    {
        $uuidArr = Redis::get($this->uuidKey);
        $uuidArr = json_decode($uuidArr,true);
        $uuid = $uuidArr['uuid'];               //获取uuid
        $code = $uuidArr['code'];               //获取状态码
        $tip = 1;                               //默认tip为1 未扫码
        switch($code)
        {
            case 0:
                $tip = 1;
                break;
            case 400:
                $uuid = WxGetItem::getUuid();
                $tip = 1;
                break;
            case 201:
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
            exit();
        }

        $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=".$this->TurnTime;
        $queue = new RequestHandel($url);
        $res = $queue->request(array(),'GET',0,array('window.QRLogin.code = 200; window.QRLogin.uuid = "'=>'','";'=>''),0,'body');
        if(!$res){              //无操作
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), '等待.....');
            $uuidArr['code'] = 0;
            Redis::set($this->uuidKey,json_encode($uuidArr));
            throw new Exception("等待.......");
        }else if(strpos($res, 'window.code=201;')){        //通过扫码
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), $res);
            $uuidArr['code'] = 201;
            Redis::set($this->uuidKey,json_encode($uuidArr));
            sleep(3);
            throw new Exception("等待扫码.......");
        }else if(strpos($res, 'window.code=200;')){         //登录
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), $res);
            $uuidArr['code'] = 200;
            Redis::set($this->uuidKey,json_encode($uuidArr));
            exit();
        }else if(strpos($res, 'window.code=400;') || strpos($res, 'window.code=408;')){     //过期
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), $res);
            $uuidArr['code'] = 400;
            Redis::set($this->uuidKey,json_encode($uuidArr));
            throw new Exception("链接过期.......");
        }else{
            Redis::hset($this->testMsgKey, date('Y-m-d H:i:s'), $res);
            exit();
        }
    }

}