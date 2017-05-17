<?php
/*
 *  请求辅助类
 */
namespace App\Libs;

class RequestHandel
{
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    //
    public function request($array=array(),$request='GET',$cookie='',$json=0,$data='all')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(json_encode($array)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);      //超时时间设置
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        if($cookie!=''){
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        if(!$content){
            return $content;
        }
        list($header, $body) = explode("\r\n\r\n", $content);
        $cookie = '';
        $header = explode('Set-Cookie: ',$header);
        foreach ($header as $key => $value) {
            if($key!=0){
                $string = explode(' Domain=',$value);
                $cookie = $cookie.$string[0];
            }
        }
        if($json==1){
            $body = json_decode($body,true);
        }
        $content = array(
            'cookie' => $cookie,
            'body' => $body
        );
        if($data=='all'){
            return $content;
        }else{
            return $content[$data];
        }
    }
}