<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assigned extends Model
{
    protected $table      = 'assigned';
    protected $primaryKey = 'Reference_Number_1';
    protected $keyType    = 'string';
    
    public $timestamps   = true;
    public $incrementing = true;

    public function driver()
    {
        return $this->belongsTo('App\Models\User', 'idDriver', 'id');
    }

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id'); 
    }
}