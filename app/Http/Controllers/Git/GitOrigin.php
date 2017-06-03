<?php
/**
 * 拉取git代码
 * User: Peter Pan
 * Date: 2017/6/4
 * Time: 1:10
 */
namespace App\Http\Controllers\Git;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class GitOriginController extends Controller
{
    public function push()
    {
        echo 1;
        exit();
//        $output = shell_exec('ls');
//        echo "<pre>$output</pre>";
    }
}