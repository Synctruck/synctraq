<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table      = 'user';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];


    protected $fillable = ['id', 'idRole', 'name', 'nameOfOwner', 'phone', 'email', 'password', 'permissionDispatch','created_at'];

    public function role()
    {
        return $this->belongsTo('App\Models\Role', 'idRole', 'id');
    }

    public function dispatchs()
    {
        return $this->hasMany('App\Models\PackageDispatch', 'idUser');
    }

    public function routes_team()
    {
        return $this->hasMany('App\Models\TeamRoute', 'idTeam');
    }

    public function teams()
    {
        return $this->hasMany('App\Models\User', 'idTeam');
    }

    public function drivers()
    {
        return $this->hasMany('App\Models\Driver', 'idUserDispatch');
    }

    public function inbounds()
    {
        return $this->hasMany('App\Models\PackageInbound', 'idUser');
    }

    public function dispatchs_user()
    {
        return $this->hasMany('App\Models\Package', 'idUserDispatch');
    }

    public function package_not_exists()
    {
        return $this->hasMany('App\Models\PackageNotExists', 'idUser');
    }

    public function returns()
    {
        return $this->hasMany('App\Models\PackageReturn', 'idUser');
    }

    public function assigneds()
    {
        return $this->hasMany('App\Models\Assigned', 'idTeam');
    }

    //observers
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->created_at = date('Y-m-d H:i:s');
            $user->updated_at = date('Y-m-d H:i:s');
        });

        static::updating(function ($user) {
            $user->updated_at = date('Y-m-d H:i:s');
        });
    }
}
