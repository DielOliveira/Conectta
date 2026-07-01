<?php

namespace App\Observers;

use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditDeletionObserver
{
    public function deleted(Model $model): void
    {
        AuditLogger::registrar(
            acao: 'registro.excluido',
            descricao: class_basename($model).' excluido.',
            entidade: $model,
            antes: AuditLogger::snapshot($model),
        );
    }
}
