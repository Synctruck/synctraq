<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTeam extends Model
{
    protected $table      = 'payment_team';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = false;
    public $keyType      = 'string';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
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