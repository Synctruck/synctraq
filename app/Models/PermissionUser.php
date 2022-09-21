<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionUser extends Model
{
    protected $table      = 'permission_user';
    protected $primaryKey = 'id';
    public $incrementing  = true;
    public $timestamps    = false;

    protected $guarded = [];
}
