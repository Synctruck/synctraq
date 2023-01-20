<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Driver extends Model implements Auditable
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

    protected $fillable = ['id', 'idRole', 'name', 'nameOfOwner', 'phone', 'email', 'password', 'idTeam', 'nameTeam', 'idOnfleet','status'];

    public function role()
    {
        return $this->belongsTo('App\Models\Role', 'idRole', 'id');
    }

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }

    public function dispatchs()
    {
        return $this->hasMany('App\Models\PackageDispatch', 'idUserDispatch');
    }

    public function faileds()
    {
        return $this->hasMany('App\Models\PackageFailed', 'idUserDispatch');
    }

    public function history_dispatch()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idUserDispatch');
    }

    public function routes_team()
    {
        return $this->hasMany('App\Models\TeamRoute', 'idTeam');
    }

    public function package_not_exists()
    {
        return $this->hasMany('App\Models\PackageNotExists', 'idUser');
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
