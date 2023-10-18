<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class RoutesZipCode extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table      = 'routes_zip_code';
    protected $primaryKey = 'zipCode';
    protected $keyType    = 'string';

    public $autoincrementting = true;
    public $timestamps        = false;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function routes()
    {
        return $this->belongsTo('App\Models\RoutesAux', 'idRoute', 'id');
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