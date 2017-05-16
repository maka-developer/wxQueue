<?php
/*
 *  保存redis存储数据的key值
 *
 */

return [
    /*
     *  形式
     *      key=>json(array(uuid=>code))
     *  code:0=>默认
     *       201=>用户扫码完成
     *       200=>用户登录完成
     *       400/408=>链接失效
     */
    'uuid'=>[
        'key'=>'wx::uuid',
        'type'=>'string',
        'ins'=>''
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