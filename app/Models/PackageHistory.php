<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageHistory extends Model
{
    protected $table      = 'packagehistory';
    protected $primaryKey = 'id';
    protected $keyType    = 'string';

    public $timestamps   = false;
    public $incrementing = true;
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function package_manifest()
    {
        return $this->belongsTo('App\Models\PackageManifest', 'idPackage', 'Reference_Number_1');
    }

    public function package_inbound()
    {
        return $this->belongsTo('App\Models\PackageInbound', 'idPackage', 'Reference_Number_1');
    }

    public function package_dispatch()
    {
        return $this->belongsTo('App\Models\PackageDispatch', 'idPackage', 'Reference_Number_1');
    }

    public function package_delivery()
    {
        return $this->belongsTo('App\Models\PackageDelivery', 'taskDetails', 'Reference_Number_1');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'idCompany', 'id');
    }

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }

    public function driver()
    {
        return $this->belongsTo('App\Models\User', 'idUserDispatch', 'id');
    }

    public function validator()
    {
        return $this->belongsTo('App\Models\User', 'idUserInbound', 'id');
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
