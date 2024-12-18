<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTeamReturn extends Model
{
    protected $table      = 'payment_team_delivery_return';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = false;

    protected $fillable = ['id', 'description', 'statusCode', 'finalStatus'];

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
