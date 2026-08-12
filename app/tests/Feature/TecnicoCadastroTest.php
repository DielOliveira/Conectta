<?php

namespace Tests\Feature;

use App\Filament\Resources\Tecnicos\Pages\CreateTecnico;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TecnicoCadastroTest extends TestCase
{
    use RefreshDatabase;

    public function test_cpf_do_tecnico_e_validado_e_salvo_sem_mascara(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateTecnico::class)
            ->fillForm(['nome' => 'Técnico CPF Inválido', 'cpf' => '529.982.247-24', 'is_ativo' => true])
            ->call('create')
            ->assertHasFormErrors(['cpf']);

        Livewire::test(CreateTecnico::class)
            ->fillForm(['nome' => 'Técnico CPF Válido', 'cpf' => '529.982.247-25', 'is_ativo' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $tecnico = Tecnico::query()->where('nome', 'Técnico CPF Válido')->firstOrFail();
        $this->assertSame('52998224725', $tecnico->cpf);
        $this->assertSame('529.982.247-25', Tecnico::formatarCpf($tecnico->cpf));
    }
}
