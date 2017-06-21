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
            'rr' => time()
        ];
        $queue = new RequestHandel($url);
        $res = $queue->request($post, 'POST', $data['cookie'], 1);
        if($res['body']['BaseResponse']['Ret'] != 0){
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),$res);
            Redis::set(config('rkey.code.key'), 1101);
        }else{
            Redis::hset(config('rkey.testMsg.key').date('Y-m-d H:i:s'),$res['body']);
            //1、更新synckey
            unset($data);
            $data['syncKey'] = json_encode($res['body']['SyncKey']);
            Redis::hmset(config('rkey.data.key'),$data);    //保存参数
        }
        exit();
    }

    static public function sendMsg($toUser, $content='')
    {
        $data = Redis::hgetall(config('rkey.data.key'));
        $localId = time().rand(1000000,9999999);
        $deviceId = 'e'.time().rand(10000,99999);
        $url = "https://".$data['host']."/cgi-bin/mmwebwx-bin/webwxsendmsg?pass_ticket=".$data['pass_ticket'];
        $post = [
            'BaseRequest' => [
                'DeviceID' => $deviceId,
                'Sid' => $data['wxsid'],
                'Skey' => $data['skey'],
                'Uin' => $data['wxuin']
            ],
            'Msg'=>[
                'Type'=>1,
                'Content'=>$content,
                'FromUserName'=>$data['UserName'],
                'ToUserName'=>$toUser,
                'LocalID'=>$localId,
                'ClientMsgId'=>$localId
            ]
        ];
        $queue = new RequestHandel($url);
        $res = $queue->request($post, 'POST', $data['cookie'], 1);
        if($res['body']['BaseResponse']['Ret'] != 0){
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),$res);
        }else{
            //信息发送成功处理,暂无
        }
        exit();
    }
}