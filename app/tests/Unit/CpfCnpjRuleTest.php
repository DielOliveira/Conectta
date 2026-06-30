<?php

namespace Tests\Unit;

use App\Rules\CpfCnpj;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CpfCnpjRuleTest extends TestCase
{
    #[DataProvider('validValues')]
    public function test_accepts_valid_cpf_and_cnpj_values(string $value): void
    {
        $validator = Validator::make(
            ['cpf_cnpj' => $value],
            ['cpf_cnpj' => [new CpfCnpj()]],
        );

        $this->assertTrue($validator->passes());
    }

    #[DataProvider('invalidValues')]
    public function test_rejects_invalid_cpf_and_cnpj_values(string $value): void
    {
        $validator = Validator::make(
            ['cpf_cnpj' => $value],
            ['cpf_cnpj' => [new CpfCnpj()]],
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
            'valid cnpj with mask' => ['04.252.011/0001-10'],
            'valid cnpj without mask' => ['04252011000110'],
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
            'invalid cnpj check digit' => ['04.252.011/0001-11'],
            'repeated cnpj digits' => ['11.111.111/1111-11'],
        ];
    }
}
