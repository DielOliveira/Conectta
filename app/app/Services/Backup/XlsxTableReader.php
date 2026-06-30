<?php

namespace App\Services\Backup;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class XlsxTableReader
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Nao foi possivel abrir o arquivo XLSX: ' . basename($path));
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('A primeira aba do arquivo nao foi encontrada: ' . basename($path));
        }

        $sheet = simplexml_load_string($sheetXml);
        $zip->close();

        if (! $sheet instanceof SimpleXMLElement) {
            throw new RuntimeException('Nao foi possivel ler a primeira aba do arquivo: ' . basename($path));
        }

        $rows = [];
        $headers = [];

        $rowPosition = 0;
        foreach ($sheet->sheetData->row as $row) {
            $values = [];

            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $column = $this->columnIndex($reference);
                $values[$column] = $this->cellValue($cell, $sharedStrings);
            }

            ksort($values);
            $max = $values === [] ? 0 : max(array_keys($values));
            $line = [];

            for ($i = 1; $i <= $max; $i++) {
                $line[] = $values[$i] ?? null;
            }

            if ($rowPosition === 0) {
                $headers = array_map(fn ($value): string => trim((string) $value), $line);

                $rowPosition++;

                continue;
            }

            $rowPosition++;

            if ($this->isEmpty($line)) {
                continue;
            }

            $record = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $record[$header] = $line[$index] ?? null;
            }

            $rows[] = $record;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);

        if (! $shared instanceof SimpleXMLElement) {
            return [];
        }

        $strings = [];
        foreach ($shared->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;

                continue;
            }

            $text = '';
            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): mixed
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            $index = (int) $cell->v;

            return $sharedStrings[$index] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) $cell->is->t;
        }

        if ($type === 'b') {
            return ((string) $cell->v) === '1';
        }

        if (! isset($cell->v)) {
            return null;
        }

        $value = (string) $cell->v;

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    private function columnIndex(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference)) ?: '';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index;
    }

    /**
     * @param array<int, mixed> $line
     */
    private function isEmpty(array $line): bool
    {
        foreach ($line as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
