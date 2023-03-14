<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PackagePreFailed extends Model
{
    protected $table      = 'packageprefailed';
    protected $primaryKey = 'taskOnfleet';
    protected $keyType    = 'string';

    public $timestamps   = true;
    public $incrementing = false;
}