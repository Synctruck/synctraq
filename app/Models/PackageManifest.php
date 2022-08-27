<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageManifest extends Model
{
    protected $table      = 'packagemanifest';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';

    public $timestamps = false;
    public $false      = true;

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
