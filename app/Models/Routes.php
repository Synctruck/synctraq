<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Routes extends Model
{
    protected $table      = 'routes';
    protected $primaryKey = 'id';
    protected $fillable   = ['id', 'zipCode', 'name'];

    public function teams()
    {
        return $this->hasMany('App\Models\TeamRoute', 'idRoute');
    }
}