<?php

namespace Tests\Feature\Services\Tracksolid;

use App\Services\Tracksolid\TracksolidDumpImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TracksolidDumpImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_ignores_tag_records(): void
    {
        $importacao = app(TracksolidDumpImportService::class)->import('Device.xls', str_repeat('a', 64), [
            ['IMEI' => '111', 'Model' => 'TAG'],
            ['IMEI' => '222', 'Model' => 'WETRACK2'],
        ]);

        $this->assertSame(2, $importacao->total_registros);
        $this->assertSame(1, $importacao->total_tags);
        $this->assertSame(1, $importacao->total_rastreadores);
        $this->assertSame(['222'], $importacao->dispositivos()->pluck('imei')->all());
    }
}
