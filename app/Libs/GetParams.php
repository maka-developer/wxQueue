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
        if($arr === false || empty($arr)){
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
}