<?php

namespace Tests\Unit;

use App\Rules\Cpf;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CpfRuleTest extends TestCase
{
    #[DataProvider('validValues')]
    public function test_accepts_valid_cpf_values(string $value): void
    {
        $validator = Validator::make(
            ['cpf' => $value],
            ['cpf' => [new Cpf()]],
        );

        $this->assertTrue($validator->passes());
    }

    #[DataProvider('invalidValues')]
    public function test_rejects_invalid_cpf_values(string $value): void
    {
        $validator = Validator::make(
            ['cpf' => $value],
            ['cpf' => [new Cpf()]],
        );

        $this->assertTrue($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validValues(): array
    {
        return [
            'valid cpf with mask' => ['529.982.247-25'],
            'valid cpf without mask' => ['52998224725'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidValues(): array
    {
        return [
            'invalid cpf check digit' => ['529.982.247-24'],
            'repeated cpf digits' => ['111.111.111-11'],
            'cnpj is not accepted' => ['04.252.011/0001-10'],
        ];
    }
}
