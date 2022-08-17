<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table      = 'comments';
    protected $primaryKey = 'id';

    public $timestamps   = true;
    public $incrementing = true;

    protected $fillable = ['id', 'description'];
}