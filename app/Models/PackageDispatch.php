<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PackageDispatch extends Model
{
    // use \OwenIt\Auditing\Auditable;

    protected $table      = 'packagedispatch';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';

    public $timestamps   = false;
    public $incrementing = true;

    /*protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];*/

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\idCompany', 'idTeam', 'id');
    }

    public function driver()
    {
        return $this->belongsTo('App\Models\User', 'idUserDispatch', 'id');
    }

    public function package_histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'Reference_Number_1');
    }
    
    public function package_price_company_team()
    {
        return $this->hasMany('App\Models\PackagePriceCompanyTeam', 'Reference_Number_1');
    }
    
    //observers
    /*protected static function booted()
    {
        static::creating(function ($user) {
            $user->created_at = date('Y-m-d H:i:s');
            $user->updated_at = date('Y-m-d H:i:s');
        });

        static::updating(function ($user) {
            $user->updated_at = date('Y-m-d H:i:s');
        });
    }*/
}
