<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogCall extends Model
{
    /** @use HasFactory<\Database\Factories\LogCallFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
}
