<?php
/**
 * 保存wx数据到数据库
 * User: Administrator
 * Date: 2017/6/21
 * Time: 15:06
 */

namespace App\Libs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class WxDbUserItem
{
    static public function saveUsers($users)
    {
        $dbArr = [];
        $pipe = Redis::pipeline();
        if(!empty($users['MemberList'])){
            foreach($users['MemberList'] as $key=>$value){
                $data[] = $value['UserName'];
                $pipe->sadd(config('rkey.users.key'),$value['UserName']);
                $dbArr[] = [
                    'uin'=>$value['Uin'],
                    'UserName'=>$value['UserName'],
                    'NickName'=>$value['NickName'],
                    'HeadImgUrl'=>$value['HeadImgUrl'],
                    'ContactFlag'=>$value['ContactFlag'],
                    'MemberCount'=>$value['MemberCount'],
                    'Sex'=>$value['Sex'],
                    'Signature'=>$value['Signature'],
                    'StarFriend'=>$value['StarFriend'],
                    'AttrStatus'=>$value['AttrStatus'],
                    'Province'=>$value['Province'],
                    'City'=>$value['City'],
                ];
            }
            Redis::exec();
        }else{
            return false;
        }
        DB::table('users')->insert($dbArr);
        return true;
    }


}