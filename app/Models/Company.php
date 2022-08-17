<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table      = 'company';
    protected $primaryKey = 'id';

    public $timestamps   = true;
    public $incrementing = true;

    protected $fillable = ['id', 'name', 'key_api', 'key_base64'];

    public function company_status()
    {
        return $this->hasMany('App\Models\CompanyStatus', 'idCompany');
    }

    public function histories()
    {
        return $this->hasMany('App\Models\PackageHistory', 'idCompany');
    }
}