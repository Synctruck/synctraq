<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;

class PalletDispatch extends Authenticatable implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table      = 'pallet_dispatch';
    protected $primaryKey = 'number';

    public $timestamps   = false;
    public $incrementing = false;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /*public function histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idCompany');
    }*/

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