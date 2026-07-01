<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Permission;
use App\Services\ZapSign\ZapSignException;
use App\Services\ZapSign\ZapSignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContratoDocumentoController extends Controller
{
    public function __invoke(Contrato $contrato, ZapSignService $zapSign): RedirectResponse
    {
        abort_unless(
            (auth()->user()?->isAdmin() ?? false)
            || (auth()->user()?->hasPermission(Permission::CADASTRO_LEITURA) ?? false)
            || (auth()->user()?->hasPermission(Permission::CADASTRO_ESCRITA) ?? false),
            403,
        );

        if (blank($contrato->doc_token)) {
            throw new NotFoundHttpException('Documento nao encontrado para este contrato.');
        }

        try {
            $documento = $zapSign->detalhesDocumento((string) $contrato->doc_token);
        } catch (ZapSignException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        $url = $this->urlDocumento($documento, $contrato->statusContrato?->label === 'Assinado');

        if ($url === null) {
            throw new NotFoundHttpException('A ZapSign nao retornou um link de documento para este contrato.');
        }

        return redirect()->away($url);
    }

    /**
     * @param  array<string, mixed>  $documento
     */
    private function urlDocumento(array $documento, bool $assinado): ?string
    {
        $signedKeys = [
            'signed_file',
            'signed_file_url',
            'sign_file',
            'sign_file_url',
            'document.signed_file',
            'document.signed_file_url',
            'data.signed_file',
            'data.signed_file_url',
        ];

        $originalKeys = [
            'original_file',
            'original_file_url',
            'file',
            'file_url',
            'document.original_file',
            'document.original_file_url',
            'data.original_file',
            'data.original_file_url',
        ];

        $keys = $assinado
            ? [...$signedKeys, ...$originalKeys]
            : [...$originalKeys, ...$signedKeys];

        foreach ($keys as $key) {
            $url = Arr::get($documento, $key);

            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }

        return null;
    }
}
