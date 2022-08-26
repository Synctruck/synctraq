<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageDelivery extends Model
{
    protected $table      = 'packagedelivery';
    protected $primaryKey = 'taskDetails';
    protected $keyType    = 'string';

    public $timestamps   = true;
    public $incrementing = false;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    public function packages_histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'Reference_Number_1');
    }
}
