<?php
/*
 * 登录监听类，队列执行
 */
namespace App\Jobs;

use App\Libs\SendRequest;
use App\Libs\WxGetItem;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;

class WxLoading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * 队列循环执行
     * 1、获取uuid
     *
     */
    public function handle()
    {
        //首先获取uuid  和tip（是否扫码1 未扫描 0已扫描)
        $code = Redis::get(config('rkey.code.key'));     //获取uuid
        $sendRequest = new SendRequest();
        if($code < 3) {
            $sendRequest->sendLogin($code);
        }else if($code >= 3 && $code <= 5){
            WxGetItem::loginInit($code);
        }else if($code == 7){
            $sendRequest->synccheck();
        }else if($code == 102){
            Redis::set(config('rkey.code.key'), 8);
        }else if($code == 107){
            Redis::set(config('rkey.code.key'), 9);
        }else if($code == 1101){    //预留退出

        }
    }
}
