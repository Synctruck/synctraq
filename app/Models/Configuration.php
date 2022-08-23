<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table      = 'configuration';
    protected $primaryKey = 'id';
    
    public $timestamps   = true;
    public $incrementing = true;
}