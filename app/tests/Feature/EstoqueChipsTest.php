<?php

namespace Tests\Feature;

use App\Filament\Pages\EstoqueChips;
use App\Models\Chip;
use App\Models\Operadora;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class EstoqueChipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operadora_ids_are_stable(): void
    {
        $this->assertSame([
            1 => 'ALGAR 5 OP',
            2 => 'ARQIA',
            3 => 'ARQIA DUAL C',
            4 => 'CLARO',
            5 => 'CONNECT ONE CLARO',
            6 => 'TIM',
            7 => 'VIVO',
        ], Operadora::query()->orderBy('id')->pluck('nome', 'id')->all());
    }

    public function test_chip_form_does_not_offer_tracker_link(): void
    {
        $this->actingAs($this->admin());
        $this->statusDisponivel();

        Livewire::test(EstoqueChips::class)
            ->assertFormFieldDoesNotExist('rastreador_id');
    }

    public function test_editing_chip_preserves_existing_tracker_link(): void
    {
        $this->actingAs($this->admin());

        $status = $this->statusDisponivel();
        $tecnico = Tecnico::query()->create(['nome' => 'Tecnico Chip']);
        $chip = Chip::query()->create([
            'numero_chip' => '5562955554444',
            'iccid' => '89550000000000000005',
            'status_rastreador_id' => $status->id,
            'tecnico_id' => $tecnico->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'modelo' => 'Modelo Chip',
            'imei' => '555555555555555',
            'chip_id' => $chip->id,
            'tecnico_id' => $tecnico->id,
            'status_rastreador_id' => $status->id,
            'is_estoque' => true,
        ]);

        Livewire::test(EstoqueChips::class)
            ->call('editar', $chip->id)
            ->fillForm([
                'fornecedor' => 'Fornecedor Atualizado',
                'operadora_id' => Operadora::query()->where('nome', 'CLARO')->value('id'),
                'numero_chip' => '62955554444',
                'iccid' => $chip->iccid,
                'status_rastreador_id' => $status->id,
                'tecnico_id' => $tecnico->id,
            ])
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertSame($chip->id, $rastreador->refresh()->chip_id);
        $this->assertSame('Fornecedor Atualizado', $chip->refresh()->fornecedor);
        $this->assertSame('5562955554444', $chip->numero_chip);
        $this->assertSame(4, $chip->operadora_id);
        $this->assertSame('CLARO', $chip->operadora);
    }

    private function admin(): User
    {
        return User::query()->create([
            'name' => 'Admin Chips',
            'email' => 'admin-chips@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);
    }

    private function statusDisponivel(): StatusRastreador
    {
        return StatusRastreador::query()->create([
            'label' => 'Disponivel',
            'order' => 1,
            'is_active' => true,
        ]);
    }
}
