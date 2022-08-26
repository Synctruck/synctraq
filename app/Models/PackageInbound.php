<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageInbound extends Model
{
    protected $table      = 'packageinbound';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';

    public $timestamps = true;
    public $false      = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'idUser', 'id');
    }

    public function package_histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idPackage');
    }
}
