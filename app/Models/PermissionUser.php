<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PermissionUser extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table      = 'permission_user';
    protected $primaryKey = 'id';
    public $incrementing  = true;
    public $timestamps    = false;

    protected $guarded = [];
}
