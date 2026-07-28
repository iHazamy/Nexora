<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['name', 'description', 'price'];
    protected function casts(): array { return ['price' => 'decimal:2']; }
}
