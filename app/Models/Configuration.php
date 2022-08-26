<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table      = 'configuration';
    protected $primaryKey = 'id';

    public $timestamps   = true;
    public $incrementing = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
}
