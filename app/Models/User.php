<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use DB;

class User extends Authenticatable  implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table      = 'user';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $appends = ['url_image'];

    protected $fillable = ['id', 'idRole', 'idCellar', 'name', 'nameOfOwner', 'phone', 'email', 'password', 'permissionDispatch','created_at','status'];

    //scopes
    public function scopeRole($query,$value)
    {
        if ($value!= '' || $value!= 0)
            return $query->where('idRole', $value);
    }
    public function scopeStatus($query,$value)
    {
        if ($value!= '' || $value!= 0 || $value!= 'All')
            return $query->where('status', $value);
    }
     /** Relaciones */
    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission','permission_user','user_id','permission_id');
    }
    public function role()
    {
        return $this->belongsTo('App\Models\Role', 'idRole', 'id');
    }

    public function cellar()
    {
        return $this->belongsTo('App\Models\Cellar', 'idCellar', 'id');
    }

    public function aux_dispatch()
    {
        return $this->hasMany('App\Models\AuxDispatchUser', 'idUser');
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
        return $this->hasMany('App\Models\Driver', 'idTeam');
    }

    public function faileds_team()
    {
        return $this->hasMany('App\Models\PackageFailed', 'idTeam');
    }

    public function inbounds()
    {
        return $this->hasMany('App\Models\PackageInbound', 'idUser');
    }

    public function histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idUser');
    }

    public function histories_teams()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idTeam');
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

    public function blockeds()
    {
        return $this->hasMany('App\Models\PackageBlocked', 'idUser');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\PaymentTeam', 'idTeam');
    }

    public function payments_team()
    {
        return $this->hasMany('App\Models\PaymentTeamReturn', 'idTeam');
    }

    public function reverts()
    {
        return $this->hasMany('App\Models\ToReversePackages', 'idTeam');
    }

    public function payments_payable()
    {
        return $this->hasMany('App\Models\PaymentTeam', 'idUserPayable');
    }

    public function payments_paid()
    {
        return $this->hasMany('App\Models\PaymentTeam', 'idUserPaid');
    }

    public function to_deduct_lost()
    {
        return $this->hasMany('\App\Models\ToDeductLostPackages', 'idTeam');
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

     //accessors
     public function getUrlImageAttribute()
     {
         if($this->image == null || $this->image == ''){
            return env('APP_URL').'/avatar/default.png';
         }
         return env('APP_URL').'/avatar/'.$this->image;
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
