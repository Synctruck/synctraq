<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Company extends Authenticatable
{
    protected $table      = 'company';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = true;

    protected $fillable = ['id', 'name', 'key_api', 'key_base64'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function company_status()
    {
        return $this->hasMany('App\Models\CompanyStatus', 'idCompany');
    }

    public function histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idCompany');
    }

    public function manifest()
    {
        return $this->hasMany('App\Models\PackageManifest', 'idCompany');
    }

    public function inbound()
    {
        return $this->hasMany('App\Models\PackageInbound', 'idCompany');
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
