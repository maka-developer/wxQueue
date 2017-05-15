<?php
/*
 * 登录监听类，队列执行
 */
namespace App\Jobs;

use App\Libs\RequestHandel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;

class WxLoading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $uuidKey;
    private $tip;
    private $TurnTime;
    private $testMsgKey;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tip)
    {
        // redis-key赋值
        $this->uuidKey = config('rkey.uuid.key');
        $this->tip = $tip;
        $this->TurnTime = time() . '000';
        $this->testMsgKey = config('rkey.testMsg.key');
    }

    /**
     * 队列循环执行
     * 1、获取uuid
     *
     */
    public function handle()
    {
        //首先获取uuid  和tip（是否扫码1 未扫描 0已扫描)
        $uuidArr = Redis::zRevRange($this->uuidKey, 0, -1);
        $uuid = $uuidArr[0];
        $tip = $this->tip;
        //调用接口
        $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?uuid=$uuid&tip=$tip&_=".$this->TurnTime;
        $queue = new RequestHandel($url);
        $res = $queue->request(array(),'GET',0,array('window.QRLogin.code = 200; window.QRLogin.uuid = "'=>'','";'=>''),0,'body');
        if(!$res){              //无操作
            Redis::hset($this->testMsgKey, '等待.....', date('Y-m-d H:i:s'));
            dispatch(new WxLoading('1'));
            exit();
        }
        if(strpos($res, 'window.code=201;')){        //通过扫码
            Redis::hset($this->testMsgKey, $res, date('Y-m-d H:i:s'));
            sleep(8);
            dispatch(new WxLoading('0'));
            exit();
        }
        if(strpos($res, 'window.code=200;')){
            Redis::hset($this->testMsgKey, $res, date('Y-m-d H:i:s'));
            exit();
        }
    }
}
