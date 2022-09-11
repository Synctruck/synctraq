<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;

class User extends Authenticatable
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

     /** Relaciones */
    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission','permission_user','user_id','permission_id');
    }
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

    //obtiene todos los permisos por rol y por usuario
    public static function allPermisions($id_user,$id_role)
    {
        return DB::select("SELECT p.id, p.name, p.slug
                            FROM permission p
                            INNER JOIN permission_user pu
                            ON p.id = pu.permission_id
                            WHERE pu.user_id = $id_user
                            UNION
                            SELECT permiss.id, permiss.name, permiss.slug
                            FROM permission_role pr
                            INNER JOIN permission permiss ON permiss.id = pr.permission_id
                            WHERE pr.role_id = $id_role");
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
