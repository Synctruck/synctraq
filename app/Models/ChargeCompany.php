<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeCompany extends Model
{
    protected $table      = 'charge_company';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = false;

    protected $fillable = ['id', 'description', 'statusCode', 'finalStatus'];

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
}
