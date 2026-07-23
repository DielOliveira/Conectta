<?php

namespace Tests\Unit;

use App\Support\ChipNumber;
use PHPUnit\Framework\TestCase;

class ChipNumberTest extends TestCase
{
    public function test_it_extracts_the_local_number_from_a_canonical_number(): void
    {
        $this->assertSame('62999999999', ChipNumber::local('5562999999999'));
    }

    public function test_it_stores_a_masked_number_in_canonical_format(): void
    {
        $this->assertSame('5562999999999', ChipNumber::canonical('(62) 99999-9999'));
    }

    public function test_validation_requires_a_valid_ddd_and_nine_digit_mobile_number(): void
    {
        $this->assertSame(1, preg_match(ChipNumber::LOCAL_REGEX, '62999999999'));
        $this->assertSame(0, preg_match(ChipNumber::LOCAL_REGEX, '999999999'));
        $this->assertSame(0, preg_match(ChipNumber::LOCAL_REGEX, '00999999999'));
        $this->assertSame(0, preg_match(ChipNumber::LOCAL_REGEX, '62899999999'));
    }
}
