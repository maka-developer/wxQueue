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
    static public function getMessage($selector = '')
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
            'rr' => time()
        ];
        $queue = new RequestHandel($url);
        $res = $queue->request($post, 'POST', $data['cookie'], 1);
        $resArr['res'] = $res['body']['AddMsgList'];
        $resArr['selector'] = $selector;
        /*测试用
        */
        Redis::hset(config('rkey.log.key'), date('Y-m-d H:i:s'),json_encode($resArr));
        /**/
        if($res['body']['BaseResponse']['Ret'] != 0){
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),$resArr);
            Redis::set(config('rkey.code.key'), 1101);
        }else{
            $str = "code=0;";
            $str .= "msg=".json_encode($res['body']['AddMsgList']).";";
            $str .= "selector=".json_encode($selector).";";
            Redis::hset(config('rkey.msgs.key'),date('Y-m-d H:i:s'),$str);
            //1、更新synckey
            unset($data);
            $data['syncKey'] = json_encode($res['body']['SyncKey']);
            Redis::hmset(config('rkey.data.key'),$data);    //保存参数
        }
        exit();
    }
    
}