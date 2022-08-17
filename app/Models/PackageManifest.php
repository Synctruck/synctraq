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
}