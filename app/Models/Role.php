<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table      = 'role';
    protected $primaryKey = 'id';
    public $timestamps   = false;
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];


    public function users()
    {
        return $this->hasMany('App\Models\User', 'idRole');
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
