<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PalletPreRtsDispatch extends Model
{
    protected $table      = 'pallet_rts_dispatch';
    protected $primaryKey = 'bolNumber';
    protected $keyType    = 'string';

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