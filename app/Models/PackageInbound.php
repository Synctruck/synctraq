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

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'idUser', 'id');
    }

    public function package_histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idPackage');
    }
}