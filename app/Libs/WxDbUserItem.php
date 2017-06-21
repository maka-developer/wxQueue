<?php
/**
 * 保存wx数据到数据库
 * User: Administrator
 * Date: 2017/6/21
 * Time: 15:06
 */

namespace App\Libs;

use Illuminate\Support\Facades\DB;

class WxDbUserItem
{
    static public function saveUsers($users)
    {
        $dbArr = [];
        if(!empty($users['MemberList'])){
            foreach($users['MemberList'] as $key=>$value){
                $dbArr[] = [
                    'uin'=>$value['Uin'],
                    'UserName'=>$value['UserName'],
                    'NickName'=>urlencode($value['NickName']),
                    'HeadImgUrl'=>$value['HeadImgUrl'],
                    'ContactFlag'=>$value['ContactFlag'],
                    'MemberCount'=>$value['MemberCount'],
                    'Sex'=>$value['Sex'],
                    'Signature'=>urlencode($value['Signature']),
                    'StarFriend'=>$value['StarFriend'],
                    'AttrStatus'=>$value['AttrStatus'],
                    'Province'=>$value['Province'],
                    'City'=>$value['City'],
                ];
            }
        }else{
            return false;
        }
        DB::table('users')->insert($dbArr);
        return true;
    }
}