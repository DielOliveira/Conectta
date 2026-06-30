<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'order', 'is_active'])]
class StatusCliente extends Model
{
    protected $table = 'status_clientes';
}
