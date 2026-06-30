<?php

namespace Tests\Unit;

use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_application_uses_brazilian_portuguese_locale(): void
    {
        $this->assertSame('pt_BR', app()->getLocale());
        $this->assertSame('pt_BR', config('app.fallback_locale'));
    }

    public function test_validation_messages_are_in_portuguese(): void
    {
        $this->assertSame(
            'O campo data de adesao e obrigatorio.',
            __('validation.required', ['attribute' => 'data de adesao']),
        );

        $this->assertSame(
            'O campo CPF/CNPJ ja esta em uso.',
            __('validation.unique', ['attribute' => 'CPF/CNPJ']),
        );
    }
}
