<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unassigned extends Model
{
    protected $table      = 'unassigned';
    protected $primaryKey = 'id';
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