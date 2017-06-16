<?php
/**
 * 记录log日志.
 * User: Administrator
 * Date: 2017/6/16
 * Time: 10:50
 */

namespace App\Libs;

use App\Model\LogModel;

class RecordLog
{

    static public function log($content, $outtime = '', $title = '')
    {
        if($content == ''){
            return false;
        }
        if(is_array($content)){
            $str = '';
            foreach($content as $key=>$value){
                $str .= $key .'=>'.$value.';';
            }
            $content = $str;
        }
        return 1;
        $log = new LogModel();
        $log['msg'] = $content;
        if($outtime != ''){
            $log['outtime'] = $outtime;
        }
        if($title != ''){
            $log['title'] = $title;
        }
        $log->save();
        return true;
    }
}