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
    ];

    public $timestamps   = false;
    public $incrementing = true;

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'idUser', 'id');
    }
}