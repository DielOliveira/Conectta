<?php

namespace Tests\Feature;

use App\Models\Chip;
use App\Models\Rastreador;
use App\Models\StatusRastreador;
use App\Services\Estoque\EquipamentoStatusWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class EquipamentoStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_equipment_always_enters_as_available(): void
    {
        [$disponivel, $ativo] = $this->statuses();

        $chip = Chip::query()->create([
            'numero_chip' => '5562999999001',
            'status_rastreador_id' => $ativo->id,
        ]);
        $rastreador = Rastreador::query()->create([
            'imei' => '900000000000001',
            'status_rastreador_id' => $ativo->id,
        ]);

        $this->assertSame($disponivel->id, $chip->status_rastreador_id);
        $this->assertSame($disponivel->id, $rastreador->status_rastreador_id);
    }

    public function test_chip_status_cannot_be_changed_outside_a_system_workflow(): void
    {
        [, $ativo] = $this->statuses();
        $chip = Chip::query()->create(['numero_chip' => '5562999999002']);

        $this->expectException(ValidationException::class);

        $chip->update(['status_rastreador_id' => $ativo->id]);
    }

    public function test_tracker_status_cannot_be_changed_outside_a_system_workflow(): void
    {
        [, $ativo] = $this->statuses();
        $rastreador = Rastreador::query()->create(['imei' => '900000000000002']);

        $this->expectException(ValidationException::class);

        $rastreador->update(['status_rastreador_id' => $ativo->id]);
    }

    public function test_system_workflow_can_change_equipment_status(): void
    {
        [, $ativo] = $this->statuses();
        $chip = Chip::query()->create(['numero_chip' => '5562999999003']);
        $rastreador = Rastreador::query()->create(['imei' => '900000000000003']);

        EquipamentoStatusWorkflow::executar(function () use ($ativo, $chip, $rastreador): void {
            $chip->update(['status_rastreador_id' => $ativo->id]);
            $rastreador->update([
                'status_rastreador_id' => $ativo->id,
            ]);
        });

        $this->assertSame($ativo->id, $chip->refresh()->status_rastreador_id);
        $this->assertSame($ativo->id, $rastreador->refresh()->status_rastreador_id);
    }

    /**
     * @return array{StatusRastreador, StatusRastreador}
     */
    private function statuses(): array
    {
        return [
            StatusRastreador::query()->create([
                'label' => 'Disponivel',
                'order' => 1,
                'is_active' => true,
            ]),
            StatusRastreador::query()->create([
                'label' => 'Ativo',
                'order' => 2,
                'is_active' => true,
            ]),
        ];
    }
}
