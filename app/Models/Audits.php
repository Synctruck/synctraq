<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audits extends Model
{
    protected $table      = 'audits';
    protected $primaryKey = 'id';

    public $timestamps   = false;
    public $incrementing = true;

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