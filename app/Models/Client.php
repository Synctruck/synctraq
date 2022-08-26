<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table      = 'client';
    protected $primaryKey = 'id';
    protected $fillable   = ['id', 'name'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
}
