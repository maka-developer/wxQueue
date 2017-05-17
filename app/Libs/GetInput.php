<?php
/*
 *  获取链接中参数
 */

namespace App\Libs;

class GetInput
{

    static public function getItem($data)
    {
        preg_match_all('#"(.*?)"#i', $data, $matches);
        $url = $matches[1][0];
        $info = substr(strstr($url,'?'),1);
        $arr = explode('&',$info);
        $resArr = array();
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
}