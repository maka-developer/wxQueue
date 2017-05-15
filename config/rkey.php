<?php
/*
 *  保存redis存储数据的key值
 *
 */

return [
    /*
     *  形式
     *      key=>{
     *              'uuid' : date('YmdHis');
     *           }
     */
    'uuid'=>[
        'key'=>'wx::uuid',
        'type'=>'有序集合',
        'ins'=>'保存wxuuid的redis，最多保存10条uuid'
    ],

    /*
     *   形式
     *      key=>{
     *              'msg'  : data('YmdHis');
     *          }
     */
    'resMsg'=>[
        'key'=>'wx::resMsg',
        'type'=>'有序集合',
        'ins'=>'保存wx请求返回值数据'
    ]
];