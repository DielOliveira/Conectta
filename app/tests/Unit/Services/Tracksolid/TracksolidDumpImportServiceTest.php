<?php

namespace Tests\Unit\Services\Tracksolid;

use App\Services\Tracksolid\TracksolidDumpImportService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TracksolidDumpImportServiceTest extends TestCase
{
    #[DataProvider('plates')]
    public function test_extracts_brazilian_plate_from_device_name(string $name, ?string $expected): void
    {
        $this->assertSame($expected, (new TracksolidDumpImportService)->extractPlate($name));
    }

    public static function plates(): array
    {
        return [
            ['SCA-9A95 CG 160 START PABLO', 'SCA9A95'],
            ['Strada velha - NLJ-1597', 'NLJ1597'],
            ['sem placa cadastrada', null],
            ['GT06-55473', null],
        ];
    }
}
