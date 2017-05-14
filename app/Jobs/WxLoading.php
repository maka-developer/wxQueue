<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;

class WxLoading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $url;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($url)
    {
        //
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
        curl_setopt($ch , CURLOPT_URL,$this->url);//设置访问的地址
        curl_setopt($ch , CURLOPT_RETURNTRANSFER,1);//获取的信息返回

        $output = curl_exec($ch);//采集

        if(curl_error($ch)){
            Redis::sadd('duilieceshikey','err:'.curl_error($ch));
        }
        Redis::sadd('duilieceshikey','success:'.$output);
    }
}
