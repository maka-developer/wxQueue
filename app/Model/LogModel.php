<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LogModel extends Model{

    protected $table = 'log';

    protected $primaryKey = 'id';

    public $timestamps = false;
}