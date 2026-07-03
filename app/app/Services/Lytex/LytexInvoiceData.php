<?php

namespace App\Services\Lytex;

class LytexInvoiceData
{
    public static function linhaDigitavel(array $response): ?string
    {
        $transactionValue = self::transactionValue($response, 'boleto', [
            'boleto.digitableLine',
            'boleto.DigitableLine',
            'Boleto.DigitableLine',
        ]);

        if ($transactionValue !== null) {
            return $transactionValue;
        }

        return self::stringFromPaths($response, [
            'paymentMethods.boleto.digitableLine',
            'paymentMethods.boleto.DigitableLine',
            'paymentMethods.Boleto.DigitableLine',
            'paymentMethods.Boleto.digitableLine',
            'PaymentMethods.Boleto.DigitableLine',
            'PaymentMethods.boleto.digitableLine',
            'PaymentMethods.boleto.DigitableLine',
            'boleto.digitableLine',
            'boleto.DigitableLine',
            'Boleto.DigitableLine',
        ]);
    }

    public static function pixCopiaCola(array $response): ?string
    {
        $transactionValue = self::transactionValue($response, 'pix', [
            'pix.qrcode',
            'pix.qrCode',
            'pix.Qrcode',
            'Pix.Qrcode',
            'boleto.qrCode.emv',
            'boleto.qrcode.emv',
            'Boleto.QrCode.Emv',
        ]);

        if ($transactionValue === null) {
            $transactionValue = self::transactionValue($response, 'boleto', [
                'pix.qrcode',
                'pix.qrCode',
                'pix.Qrcode',
                'Pix.Qrcode',
                'boleto.qrCode.emv',
                'boleto.qrcode.emv',
                'Boleto.QrCode.Emv',
            ]);
        }

        if ($transactionValue !== null) {
            return $transactionValue;
        }

        return self::stringFromPaths($response, [
            'paymentMethods.pix.copyPaste',
            'paymentMethods.pix.copyAndPaste',
            'paymentMethods.pix.qrCode',
            'paymentMethods.pix.qrcode',
            'paymentMethods.pix.Qrcode',
            'paymentMethods.Pix.CopyPaste',
            'paymentMethods.Pix.CopyAndPaste',
            'paymentMethods.Pix.QrCode',
            'paymentMethods.Pix.Qrcode',
            'PaymentMethods.Pix.CopyPaste',
            'PaymentMethods.Pix.CopyAndPaste',
            'PaymentMethods.Pix.QrCode',
            'PaymentMethods.Pix.Qrcode',
            'pix.copyPaste',
            'pix.copyAndPaste',
            'pix.qrCode',
            'pix.qrcode',
        ]);
    }

    /**
     * @param array<int, string> $paths
     */
    private static function transactionValue(array $response, string $paymentMethod, array $paths): ?string
    {
        $transactions = data_get($response, 'transactions', []);

        if (! is_iterable($transactions)) {
            return null;
        }

        foreach ($transactions as $transaction) {
            if (! is_array($transaction)) {
                continue;
            }

            $method = str((string) data_get($transaction, 'paymentMethod', data_get($transaction, 'PaymentMethod')))
                ->lower()
                ->ascii()
                ->toString();

            if ($method !== $paymentMethod) {
                continue;
            }

            $value = self::stringFromPaths($transaction, $paths);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $paths
     */
    private static function stringFromPaths(array $response, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($response, $path);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
