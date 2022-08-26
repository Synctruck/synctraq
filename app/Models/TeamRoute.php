<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamRoute extends Model
{
    protected $table      = 'teamroute';
    protected $primaryKey = 'id';
    protected $fillable   = ['id', 'idTeam', 'idRoute'];

    public $incrementing = true;
    public $timestamps   = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function route()
    {
        return $this->belongsTo('App\Models\Routes', 'idRoute', 'id');
    }

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }
}
