<?php
/*
 *  保存redis存储数据的key值
 *
 */

return [

    'WxState'=>[
        'key' => 'wx::state',
        'type'=>'string',
        'ins'=>''
    ],

    'uuid'=>[
        'key'=>'wx::uuid',
        'type'=>'string',
        'ins'=>'用户uuid'
    ],

    'data'=>[
        'key'=>'wx::data',
        'type'=>'hash',
        'ins'=>'用户信息'
    ],

    'code'=>[
        'key'=>'wx::code',
        'type'=>'string',
        'ins'=>'全局code动态'
    ],

    'msgs'=>[
        'key'=>'wx::msg',
        'type'=>'hash',
        'ins'=>'消息记录'
    ],

    'testMsg'=>[
        'key'=>'wx::testMsg',
        'type'=>'散列',
        'ins'=>'测试用数据保存'
    ],

    'errorMsg'=>[
        'key'=>'wx::errorMsg',
        'type'=>'散列',
        'ins'=>'保存错误信息'
    ],

    'users'=>[
        'key'=>'wx::users',
        'type'=>'集合',
        'ins'=>'保存用户好友信息'
    ]
];