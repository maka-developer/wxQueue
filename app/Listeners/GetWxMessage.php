<?php

namespace App\Listeners;

use App\Events\WxMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class GetWxMessage implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  WxMessage  $event
     * @return void
     */
    public function handle(WxMessage $event)
    {

        Redis::sadd('ceshikey',$event->request);
        //
    }
}
