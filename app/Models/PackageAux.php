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
}