<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class States extends Model
{
    protected $table      = 'states';
    protected $primaryKey = 'id';
    protected $fillable   = ['id', 'name', 'filter'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
