<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class PermissionRole extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    use HasFactory;
    protected $table      = 'permission_role';
    protected $primaryKey = 'id';
    public $incrementing  = true;
    public $timestamps    = false;

    protected $guarded = [];
}
