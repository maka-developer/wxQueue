<?php
/*
 * 登录监听类，队列执行
 */
namespace App\Jobs;

use App\Libs\RequestHandel;
use App\Libs\SendRequest;
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
        $sendRequest = new SendRequest();
        $sendRequest->sendLogin(1);
    }
}
