<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageManifest extends Model
{
    protected $table      = 'packagemanifest';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';

    public $timestamps = true;
    public $false      = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
