<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['numr', 'nome'])]
class Vendedor extends Model
{
    protected $table = 'vendedores';
}
