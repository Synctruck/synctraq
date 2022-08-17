<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageReturn extends Model
{
    protected $table      = 'packagereturn';
    protected $primaryKey = 'id';
    protected $keyType    = 'string';
    
    public $timestamps   = true;
    public $incrementing = false;

    public function driver()
    {
        return $this->belongsTo('App\Models\User', 'idUserReturn', 'id');
    }
}