<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TeamRoute extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table      = 'teamroute';
    protected $primaryKey = 'id';
    protected $fillable   = ['id', 'idTeam', 'idRoute'];

    public $incrementing = true;
    public $timestamps   = false;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function route()
    {
        return $this->belongsTo('App\Models\Routes', 'idRoute', 'id');
    }

    public function team()
    {
        return $this->belongsTo('App\Models\User', 'idTeam', 'id');
    }

    //observers
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->created_at = date('Y-m-d H:i:s');
            $user->updated_at = date('Y-m-d H:i:s');
        });

        static::updating(function ($user) {
            $user->updated_at = date('Y-m-d H:i:s');
        });
    }
}
