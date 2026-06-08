<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $fillable = [  'user_id',  'title', 'description', 'start_date',  'due_date',    'status' ] ;
}
