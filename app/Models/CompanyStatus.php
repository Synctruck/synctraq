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

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'idCompany', 'id');
    }
}
