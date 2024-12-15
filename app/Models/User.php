<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, SoftDeletes;
    protected $guarded = [];
    protected $table = 'users';

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function role_master()
    {
        return $this->belongsTo(RoleMaster::class, 'role_master_id', 'id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
