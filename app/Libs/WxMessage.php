<?php
/**
 * 消息处理.
 * Date: 2017/6/8
 * Time: 11:05
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;

class WxMessage
{
    /*
     * 获取消息
     */
    static public function getMessage()
    {
        $data = Redis::hgetall(config('rkey.data.key'));
        dd($data);
    }
}