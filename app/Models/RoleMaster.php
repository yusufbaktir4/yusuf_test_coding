<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleMaster extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $table = 'role_masters';
}
