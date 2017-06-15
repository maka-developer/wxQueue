<?php
/**
 * 消息处理.
 * Date: 2017/6/8
 * Time: 11:05
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;
use App\Libs\GetParams;

class WxMessage
{
    /*
     * 获取消息
     */
    static public function getMessage()
    {
        $deviceId = 'e'.time().rand(10000,99999);
        $data = Redis::hgetall(config('rkey.data.key'));
        $url = "https://".$data['host']."/cgi-bin/mmwebwx-bin/webwxsync?sid=".$data['wxsid']."&skey=".$data['skey'];
        $post = [
            'BaseRequest' => [
                'DeviceID' => $deviceId,
                'Sid' => $data['wxsid'],
                'Skey' => $data['skey'],
                'Uin' => $data['wxuin']
            ],
            'SyncKey' => json_decode($data['syncKey'],true),
            'rr' => time().rand(000,999)
        ];
        $queue = new RequestHandel($url);
        $res = $queue->request($post, 'POST', $data['cookie'], 1);
        $resArr['res'] = $res;
        $resArr['url'] = $url;
        $resArr['post'] = $post;
        Redis::hset(config('rkey.log.key'), 'msg'.date('Y-m-d H:i:s'),json_encode($resArr));
        if($res['body']['BaseResponse']['Ret'] != 0){
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($res));
            Redis::set(config('rkey.code.key'), 1101);
        }else{
            Redis::hset(config('rkey.msgs.key'),date('Y-m-d H:i:s'),json_encode($res));
            Redis::set(config('rkey.code.key'), 7);
            //1、更新synckey
            unset($data);
            $data['syncKey'] = json_encode($res['body']['SyncKey']);
//            $data['syncKeyStr'] = GetParams::updateSyncKey($res['body']['SyncKey']);
            $data['cookie'] = GetParams::mergeCookie($res['cookie']);
            Redis::hmset(config('rkey.data.key'),$data);    //保存参数
        }
        exit();
    }
}