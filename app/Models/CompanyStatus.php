<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyStatus extends Model
{
    protected $table      = 'company_status';
    protected $primaryKey = 'id';
    protected $keyType    = 'string';

    public $timestamps   = true;
    public $incrementing = false;

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'idCompany', 'id');
    }
}