<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table      = 'user';
    protected $primaryKey = 'id';

    public $timestamps   = true;
    public $incrementing = true;

    protected $fillable = ['id', 'idRole', 'name', 'nameOfOwner', 'phone', 'email', 'password', 'permissionDispatch'];

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

    public function drivers()
    {
        return $this->hasMany('App\Models\Driver', 'idTeam');
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
}