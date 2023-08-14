<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ToReversePackages extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    
    protected $table      = 'to_reverse_packages';
    protected $primaryKey = 'shipmentId';
    protected $keyType    = 'string';

    public $timestamps   = false;
    public $incrementing = false;

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

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }
}