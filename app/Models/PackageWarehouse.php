<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageWarehouse extends Model
{
    protected $table      = 'packagewarehouse';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';
    protected $casts      = [

        'created_at' => 'date:Y-m-d H:i:s',
        'updated_at' => 'date:Y-m-d H:i:s',
    ];

    public $timestamps   = false;
    public $incrementing = true;

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'idUser', 'id');
    }

    protected static function booted()
    {
        static::creating(function($user){

            $user->created_at = date('Y-m-d H:i:s');
            $user->updated_at = date('Y-m-d H:i:s');
        });

        static::updating(function($user){

            $user->updated_at = date('Y-m-d H:i:s');
        });
    }
}