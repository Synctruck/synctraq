<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table      = 'role';
    protected $primaryKey = 'id';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
    public function users()
    {
        return $this->hasMany('App\Models\User', 'idRole');
    }
}
