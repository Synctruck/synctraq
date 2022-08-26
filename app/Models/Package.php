<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table      = 'package';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';

    public $timestamps   = true;
    public $incrementing = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function user_inbound()
    {
        return $this->belongsTo('App\Models\User', 'idUserInbound', 'id');
    }

    public function user_dispatch()
    {
        return $this->belongsTo('App\Models\User', 'idUserDispatch', 'id');
    }

    public function dispatchs()
    {
        return $this->hasMany('App\Models\PackageDispatch', 'idPackage');
    }

    public function histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idPackage');
    }
}
