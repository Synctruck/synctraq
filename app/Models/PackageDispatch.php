<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageDispatch extends Model
{
    protected $table      = 'packagedispatch';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';

    public $timestamps   = true;
    public $incrementing = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
    public function driver()
    {
        return $this->belongsTo('App\Models\User', 'idUserDispatch', 'id');
    }

    public function package_histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'Reference_Number_1');
    }
}
