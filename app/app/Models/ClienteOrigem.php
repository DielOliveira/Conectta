<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'order', 'is_active'])]
class ClienteOrigem extends Model
{
    protected $table = 'cliente_origens';
}
