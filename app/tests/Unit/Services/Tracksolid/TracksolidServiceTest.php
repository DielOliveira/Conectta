<?php

namespace Tests\Unit\Services\Tracksolid;

use App\Services\Tracksolid\TracksolidService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracksolidServiceTest extends TestCase
{
    public function test_it_signs_and_obtains_a_token_without_exposing_the_secret(): void
    {
        $this->configure();

        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'message' => 'success',
                'result' => [
                    'accessToken' => 'access-token',
                    'refreshToken' => 'refresh-token',
                    'expiresIn' => 7200,
                ],
            ]),
        ]);

        $token = (new TracksolidService)->token();

        $this->assertSame('access-token', $token['accessToken']);

        Http::assertSent(function ($request): bool {
            $data = $request->data();
            $unsigned = $data;
            unset($unsigned['sign']);
            ksort($unsigned, SORT_STRING);
            $content = collect($unsigned)
                ->map(fn (mixed $value, string $name): string => $name.(string) $value)
                ->implode('');
            $expected = strtoupper(md5('super-secret'.$content.'super-secret'));

            return $request->url() === 'https://us-open.tracksolidpro.com/route/rest'
                && $data['method'] === 'jimi.oauth.token.get'
                && $data['sign'] === $expected
                && ! str_contains((string) $request->body(), 'super-secret');
        });
    }

    public function test_it_reads_the_device_inventory(): void
    {
        $this->configure();

        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'message' => 'success',
                'result' => [
                    ['imei' => '123456789012345', 'deviceName' => 'Veiculo'],
                ],
            ]),
        ]);

        $devices = (new TracksolidService)->devices('access-token');

        $this->assertSame('123456789012345', $devices[0]['imei']);
    }

    private function configure(): void
    {
        config()->set('services.tracksolid', [
            'base_url' => 'https://us-open.tracksolidpro.com/route/rest',
            'account' => 'account-1',
            'app_key' => 'app-key',
            'app_secret' => 'super-secret',
            'password_md5' => md5('password'),
            'timeout' => 10,
        ]);
    }
}
