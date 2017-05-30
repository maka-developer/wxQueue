<?php
/*
 *  保存redis存储数据的key值
 *
 */

return [
    /*
     *  形式
     *      key=>json(array(uuid=>code))
     */
    'uuid'=>[
        'key'=>'wx::uuid',
        'type'=>'string',
        'ins'=>''
    ],

    /*
     *  形式
     *      hkey=>[
     *              key=>value
     *          ]
     */
    'data'=>[
        'key'=>'wx::data',
        'type'=>'hash',
        'ins'=>'ticket, scan, cookie'
    ],

    /*
     *  形式，同uuid
     *  0   =>  获取uuid
     *  1   =>  用户扫码
     *  2   =>  用户登录
     *  3   =>  登录监听接口超时
     *
     *
     */
    'code'=>[
        'key'=>'wx::code',
        'type'=>'string',
        'ins'=>'全局code动态'
    ],

    /*
     *   形式
     *      key=>{
     *              data('YmdHis');
     *          }
     */
    'resMsg'=>[
        'key'=>'wx::resMsg',
        'type'=>'有序集合',
        'ins'=>'保存wx请求返回值数据'
    ],

    /*
     *    形式
     *      key=>{
     *             data('Y-m-d H:i:s')=>msg;
     *          }
     */
    'testMsg'=>[
        'key'=>'wx::testMsg',
        'type'=>'散列',
        'ins'=>'测试用数据保存'
    ],

    /*
     *
     *
     */
    'errorMsg'=>[
        'key'=>'wx::errorMsg',
        'type'=>'散列',
        'ins'=>'保存错误信息'
    ]
];