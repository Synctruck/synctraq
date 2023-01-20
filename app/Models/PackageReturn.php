<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PackageReturn extends Model
{
    // use \OwenIt\Auditing\Auditable;

    protected $table      = 'packagereturn';
    protected $primaryKey = 'id';
    protected $keyType    = 'string';

    public $timestamps   = false;
    public $incrementing = false;
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }

    public function driver() 
    {
        return $this->belongsTo('App\Models\User', 'idUserReturn', 'id');
    }

    public function package_price_company_team()
    {
        return $this->hasMany('App\Models\PackagePriceCompanyTeam', 'Reference_Number_1');
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
