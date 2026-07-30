<?php

namespace Tests\Unit;

use App\Filament\Resources\Clientes\Schemas\ClienteForm;
use PHPUnit\Framework\TestCase;

class ClienteNascimentoFormattingTest extends TestCase
{
    public function test_it_formats_laravel_iso_date_for_the_masked_field(): void
    {
        $this->assertSame(
            '24/09/1962',
            ClienteForm::formatNascimento('1962-09-24T03:00:00.000000Z'),
        );
    }
}
