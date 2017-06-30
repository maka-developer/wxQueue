<?php
/**
 * 消息处理.
 * Date: 2017/6/8
 * Time: 11:05
 */
namespace App\Libs;

use App\Model\GroupModel;
use App\Model\UsersModel;
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
            $resArr['content'] = '消息拉取失败';
            $resArr['res'] = $res;
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($res));
            Redis::set(config('rkey.code.key'), 1101);
        }else{
            Redis::hset(config('rkey.msgs.key'),date('Y-m-d H:i:s'),json_encode($res));
            //1、更新synckey
            unset($data);
            $data['syncKey'] = json_encode($res['body']['SyncKey']);
            Redis::hmset(config('rkey.data.key'),$data);    //保存参数
            self::putMessage($res['body']);
        }
        exit();
    }
    /*
     *  消息处理 拉群
     */
    static public function putMessage($body)
    {
        if(!$body['AddMsgList']){
            exit();
        }
        foreach($body['AddMsgList'] as $key=>$value){
            if($value['Content'] == ''){
                continue;
            }
            $from = $value['FromUserName'];     //来源
                //判断是否群组
            if(strstr($value['FromUserName'],'@@')){    //群组，判断有无指令  set:
                //首先判断是否设置指令
                $group = GroupModel::where('UserName',$from)->first();
                if ($str = strstr($value['Content'], 'set:')) {
                    if($group == null) {  //未设置命令
                        $content = explode(':', $str);
                        $content = $content[1];         //指令
                        $group = new GroupModel();
                        $group['UserName'] = $value['FromUserName'];
                        $group['instructions'] = $content;
                        $group->save();
                        self::sendMsg($value['FromUserName'], '命令设置成功');
                    } else {
                        self::sendMsg($value['FromUserName'], '命令已经设置，无需重复设置');
                        exit();
                    }
                }else{
                    exit();
                }
            }else{  //非群组
                //首先判断是否加好友请求
                if($from == 'fmessage' && $value['RecommendInfo']['Ticket'] != '' && $value['RecommendInfo']['UserName'] != ''){
                    //加好友处理
                    self::webwxverifyuser($value['RecommendInfo']['UserName'], $value['RecommendInfo']['Ticket']);
                    exit();
                }
                //其次判断是否命令
                $content = $value['Content'];
                $group = GroupModel::where('instructions',$content)->first()->toArray();
                if(!empty($group)){
                    $UserName = $group['UserName'];     //group name
                    self::updatechatroom($value['FromUserName'],$UserName);
                }else{
                    exit();
                }
                self::sendMsg($value['FromUserName'],$content);
                exit();
            }
        }
    }

    /*
     * 处理消息 判断用户是否存在
     *
    static public function putMessage($body)
    {
        if(!$body['AddMsgList']){
            exit();
        }
        $res = [];
        foreach($body['AddMsgList'] as $key=>$value){
            if($value['Content'] == ''){
                continue;
            }
            $from = $value['FromUserName'];
            $users = UsersModel::where('UserName',$from)->first();
            if($users[0] == null){  //没有好友信息
                //判断是否群组
                if(strstr($value['FromUserName'],'@@')){
                    $group['Count'] = 1;
                    $group['List'] = [
                        0=>['UserName'=>$value['FromUserName'], 'EncryChatRoomId'=>""]
                    ];
                    $gRes = WxGetItem::webwxbatchgetcontact($group);
                    Redis::hset(config('rkey.testMsg.key'),date('Y-m-d H:i:s'),json_encode($gRes));
                    if($gRes['code'] == 0){  //载入群消息成功
                        self::sendMsg($value['FromUserName'],$gRes['item']['ContactList'][0]['NickName']);
                    }else{
                        self::sendMsg($value['FromUserName'],$gRes['msg']);
                    }
                }else{  //没有好友信息
                    //查看ModContactList是否存在（新增好友）

                    exit();
                }
            }
            $res[] = $users;
        }
        return $res;
    }

    **/
    /**
     * 发送消息
     * @param $toUser string 消息接收人 UserName
     * @param $content string 消息内容
     */
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
                'Content'=>urlencode($content),
                'FromUserName'=>$data['UserName'],
                'ToUserName'=>$toUser,
                'LocalID'=>$localId,
                'ClientMsgId'=>$localId
            ]
        ];
        $queue = new RequestHandel($url);
        $res = $queue->request($post, 'POST', $data['cookie'], 1);
        if($res['body']['BaseResponse']['Ret'] != 0){
            $resArr['content'] = $content;
            $resArr['res'] = $res;
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($resArr));
        }else{
            //
        }
    }
    /**
     * 拉群
     * @param $username string 用户 UserName
     * @param $groupname string 群组 UserName
     */
    static public function updatechatroom($username,$groupname)
    {
        $data = Redis::hgetall(config('rkey.data.key'));
        $deviceId = 'e'.time().rand(10000,99999);
        $url = "https://".$data['host']."/cgi-bin/mmwebwx-bin/webwxupdatechatroom?fun=addmember&lang=zh_CN&pass_ticket=".$data['pass_ticket'];
        $post = [
            'AddMemberList'=>$username,
            'BaseRequest' => [
                'DeviceID' => $deviceId,
                'Sid' => $data['wxsid'],
                'Skey' => $data['skey'],
                'Uin' => $data['wxuin']
            ],
            'ChatRoomName'=>$groupname
        ];
        $queue = new RequestHandel($url);
        $res = $queue->request($post, 'POST', $data['cookie'], 1);
        if($res['body']['BaseResponse']['Ret'] != 0){
            // 回复拉群失败消息
            $resArr['content'] = '拉群失败';
            $resArr['res'] = $res;
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($resArr));
        }else{
            //
        }
    }
    /**
     * 添加好友
     * @param $value string 被添加用户的用户名
     * @param $verifyUserTicket string 消息列表返回验证字串
     */
    static public function webwxverifyuser($value, $verifyUserTicket)
    {
        $data = Redis::hgetall(config('rkey.data.key'));
        $deviceId = 'e'.time().rand(10000,99999);
        $url = "https://".$data['host']."/cgi-bin/mmwebwx-bin/webwxverifyuser";
        $post = [
            'BaseRequest' => [
                'DeviceID' => $deviceId,
                'Sid' => $data['wxsid'],
                'Skey' => $data['skey'],
                'Uin' => $data['wxuin']
            ],
            'skey'=>$data['skey'],
            'Opcode'=>3,
            'SceneList'=>[0=>33],
            'SceneListCount'=>1,
            'VerifyContent'=>"",
            'VerifyUserList'=>[
                0=>[
                    'Value'=>$value,
                    'VerifyUserTicket'=>$verifyUserTicket
                ]
            ],
            'VerifyUserListSize'=>1
        ];
        $queue = new RequestHandel($url);
        $res = $queue->request($post, 'POST', $data['cookie'], 1);
        if($res['body']['BaseResponse']['Ret'] != 0){
            // 加好友失败
            $resArr['content'] = '加好友失败';
            $resArr['res'] = $res;
            Redis::hset(config('rkey.errorMsg.key'),date('Y-m-d H:i:s'),json_encode($resArr));
        }else{
            //
        }
    }
}