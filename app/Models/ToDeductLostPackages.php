<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToDeductLostPackages extends Model
{
    protected $table      = 'to_deduct_lost_packages';
    protected $primaryKey = 'shipmentId';
    protected $keyType    = 'string';

    public $timestamps   = false;
    public $incrementing = true;

    public function team()
    {
        return $this->belongsTo('\App\Models\User', 'idTeam', 'id');
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