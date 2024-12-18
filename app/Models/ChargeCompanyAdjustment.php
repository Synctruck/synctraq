<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ChargeCompanyAdjustment extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table      = 'charge_company_adjustment';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = false;
    public $keyType      = 'string';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

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
    
    /*public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }

    public function user_payable()
    {
        return $this->belongsTo('App\Models\User', 'idUserPayable', 'id');
    }

    public function user_paid()
    {
        return $this->belongsTo('App\Models\User', 'idUserPaid', 'id');
    }*/

    
}