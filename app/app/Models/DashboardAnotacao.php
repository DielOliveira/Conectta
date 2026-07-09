<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardAnotacao extends Model
{
    protected $table = 'dashboard_anotacoes';

    protected $fillable = [
        'user_id',
        'conteudo',
    ];
}
