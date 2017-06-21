<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UsersModel extends Model{

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $timestamps = false;
}