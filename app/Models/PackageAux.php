<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageAux extends Model
{
    protected $table      = 'packageaux';
    protected $primaryKey = 'id';
    protected $keyType    = 'string';

    public $timestamps   = true;
    public $incrementing = false;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
