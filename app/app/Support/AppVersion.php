<?php

namespace App\Support;

class AppVersion
{
    public static function label(): string
    {
        $version = (string) config('app.version', '1.0.0');
        $commit = self::commit();

        return $commit ? "v{$version} · {$commit}" : "v{$version}";
    }

    private static function commit(): ?string
    {
        $gitDir = base_path('../.git');
        $headFile = $gitDir.'/HEAD';

        if (! is_file($headFile)) {
            return null;
        }

        $head = trim((string) file_get_contents($headFile));

        if ($head === '') {
            return null;
        }

        if (! str_starts_with($head, 'ref: ')) {
            return substr($head, 0, 7);
        }

        $refFile = $gitDir.'/'.substr($head, 5);

        if (! is_file($refFile)) {
            return null;
        }

        $commit = trim((string) file_get_contents($refFile));

        return $commit === '' ? null : substr($commit, 0, 7);
    }
}
