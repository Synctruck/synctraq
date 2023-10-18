<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTool extends Model
{
    protected $table      = 'inventory_tool';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = true;
    public $keyType      = 'string';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    public function users()
    {
        return $this->hasMany('App\Models\User', 'idCellar');
    }

    public function inventory_tool_detail()
    {
        return $this->hasMany('App\Models\InventoryToolDetail', 'idInventoryTool');
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