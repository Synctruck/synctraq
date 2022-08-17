<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageNotExists extends Model
{
    protected $table      = 'packagenotexists';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';
    
    public $timestamps   = true;
    public $incrementing = true;

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'idUser', 'id');
    }
}