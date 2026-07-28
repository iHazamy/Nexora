<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'description', 'price'];
    protected function casts(): array { return ['price' => 'decimal:2']; }
}
