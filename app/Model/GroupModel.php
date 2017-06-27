<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GroupModel extends Model{

    protected $table = 'groups';

    protected $primaryKey = 'id';

    public $timestamps = false;
}