<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageHistoryNeeMoreInformation extends Model
{
    protected $table      = 'packagehistory_needmoreinformation';
    protected $primaryKey = 'id';
    protected $keyType    = 'string';

    public $timestamps   = false;
    public $incrementing = true;
    /*protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];*/

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'idUser', 'id');
    }

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
