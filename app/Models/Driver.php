<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $table      = 'user';
    protected $primaryKey = 'id';

    public $timestamps   = true;
    public $incrementing = true;

    protected $fillable = ['id', 'idRole', 'name', 'nameOfOwner', 'phone', 'email', 'password', 'idTeam', 'nameTeam', 'idOnfleet'];

    public function role()
    {
        return $this->belongsTo('App\Models\Role', 'idRole', 'id');
    }

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }

    public function dispatchs()
    {
        return $this->hasMany('App\Models\PackageDispatch', 'idUserDispatch');
    }
}