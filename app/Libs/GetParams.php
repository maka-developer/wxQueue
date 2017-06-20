<?php
/**
 * 辅助类，提取参数
 * Date: 2017/6/2
 * Time: 14:41
 */
namespace App\Libs;

use Illuminate\Support\Facades\Redis;

class GetParams
{
    //获取传入url中的参数  带host
    static public function getItem($data)
    {
        preg_match_all('#"(.*?)"#i', $data, $matches);
        $url = $matches[1][0];
        $server = parse_url($url);
        $info = $server['query'];
        $arr = explode('&',$info);
        $resArr = array();
        $resArr['host'] = $server['host'];
        $resArr['url'] = $url;
        foreach($arr as $key=>$value){
            $strArr = str_split($value);
            $resType = 0;
            $resKey = '';
            $resValue = '';
            foreach($strArr as $item){
                if($item == '='){
                    if($resType == 0){
                        $resType = 1;
                    }else{
                        $resValue .= $item;
                    }
                }else{
                    if($resType == 0){
                        $resKey .= $item;
                    }else{
                        $resValue .= $item;
                    }
                }
            }
            $resArr[$resKey] = $resValue;
        }
        return $resArr;
    }

    //更新synckey 组合synckey字符串
    static public function updateSyncKey($arr)
    {
        if($arr === false){
            return false;
        }
        if(!is_array($arr)){
            $arr = json_decode($arr,true);
        }
        if(empty($arr)){
            return false;
        }
        $count = $arr['Count'];
        $resStr = '';
        for($i=0; $i<$count; $i++){
            $listArr = $arr['List'][$i];
            if($i == $count - 1){
                $resStr .= $listArr['Key'].'_'.$listArr['Val'];
            }else{
                $resStr .= $listArr['Key'].'_'.$listArr['Val'].'|';
            }
        }
        return $resStr;
    }

    //将cookie整合进rediscookie
    static public function mergeCookie($cookie)
    {
        $rCookie = Redis::hget(config('rkey.data.key'),'cookie');
        $rCookieArr = self::split($rCookie);
        $cookieArr = self::split($cookie);
        foreach($cookieArr as $key=>$value){    //替换
            $rCookieArr[$key] = $value;
        }
        $cookieStr = '';
        if(array_key_exists('wxloadtime',$cookieArr)){      //如果有则保存
            Redis::hset(config('rkey.data.key'), 'wxloadtime', $cookieArr['wxloadtime']);
        }
        foreach($rCookieArr as $key=>$value){
            $cookieStr .= $key."=".$value.';';
        }
        return $cookieStr;
    }

    //拆解cookie字符串=>数组
    static public function split($cookie)
    {
        if($cookie == ''){
            return false;
        }
        $strArr = str_split($cookie);
        $cookieArr = [];
        $key = '';
        $val = '';
        $isv = 0;
        foreach($strArr as $value){
            if($value == '=' && $isv == 0){
                $isv = 1;
                continue;
            }
            if($value == ';'){
                $isv = 0;
                $cookieArr[$key] = $val;
                $key = '';
                $val = '';
                continue;
            }
            if($isv == 0){
                $key .= $value;
            }else{
                $val .= $value;
            }
        }

        return $cookieArr;
    }

    //长整型相加
    static function numAdd($num, $addNum = 1)
    {
        $num_length = strlen($num);
        $add_length = strlen($addNum);
        if($add_length > 10){   //暂不支持 两个长整型相加
            return false;
        }
        if($num_length < $add_length){  //必须前一位大于后一位
            return self::numAdd($addNum,$num);
        }

        //运算
        $add = substr($num, 0, -$add_length) . (string)((int)substr($num, -$add_length) + $addNum);
        return $add;
    }
}