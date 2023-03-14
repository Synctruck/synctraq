<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryDiesel extends Model
{
    protected $table      = 'history_diesel';
    protected $primaryKey = 'id';

    public $timestamps   = true;
    public $incrementing = true;
}