<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * KHQR Service
 *
 * Generates QR payload strings conforming to the Cambodia KHQR standard
 * (based on EMVCo QR Code Specification) and verifies transactions via
 * the free Bakong Open API.
 *
 * No third-party package required — the format is implemented from scratch.
 */
class KhqrService
{
    // -----------------------------------------------------------------------
    // QR String Generation
    // -----------------------------------------------------------------------

    /**
     * Encode one TLV (Tag-Length-Value) field.
     * Tag  : 2-digit numeric string
     * Length: 2-digit zero-padded byte length of value
     * Value : the field content
     */
    private function tlv(string $tag, string $value): string
    {
        $length = str_pad(mb_strlen($value, '8bit'), 2, '0', STR_PAD_LEFT);
        return $tag . $length . $value;
    }

    /**
     * Build Tag 29 — KHQR Merchant Account Information (Bakong).
     *
     * Sub-tags:
     *   00 — AID (globally unique identifier for Bakong)
     *   01 — Bakong account  e.g. "012345678@aclb"
     *   02 — Merchant ID     (optional, set KHQR_MERCHANT_ID in .env)
     *   99 — Acquirer bank ID (optional, set KHQR_ACQUIRER_ID in .env)
     */
    private function merchantAccountInfo(): string
    {
        $inner = $this->tlv('00', 'bakong.io')
               . $this->tlv('01', config('khqr.bakong_account'));

        $merchantId = config('khqr.merchant_id');
        if ($merchantId) {
            $inner .= $this->tlv('02', (string) $merchantId);
        }

        $acquirerId = config('khqr.acquirer_id');
        if ($acquirerId) {
            $inner .= $this->tlv('99', (string) $acquirerId);
        }

        return $this->tlv('29', $inner);
    }

    /**
     * Build Tag 62 — Additional Data Field Template.
     * Sub-tag 05 carries the Bill/Reference Number.
     */
    private function additionalDataField(string $billRef): string
    {
        return $this->tlv('62', $this->tlv('05', $billRef));
    }

    /**
     * CRC-16/CCITT-FALSE checksum (polynomial 0x1021, init 0xFFFF).
     * The entire QR string including the "6304" suffix (CRC tag + placeholder)
     * is fed into this function; the result is appended as 4 uppercase hex chars.
     */
    private function crc16(string $data): string
    {
        $crc = 0xFFFF;
        $len = mb_strlen($data, '8bit');

        for ($i = 0; $i < $len; $i++) {
            $crc ^= (ord($data[$i]) & 0xFF) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000)
                    ? (($crc << 1) ^ 0x1021) & 0xFFFF
                    : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Generate a KHQR dynamic QR payload string.
     *
     * @param  float   $amount    Transaction amount (e.g. 12.50)
     * @param  string  $billRef   Bill reference / receipt number shown to payer
     * @param  string  $currency  'USD' (default) or 'KHR'
     * @return string  Full KHQR payload — pass to a QR encoder library on the frontend
     */
    public function generate(float $amount, string $billRef, string $currency = 'USD'): string
    {
        $currencyCode = $currency === 'KHR' ? '116' : '840';

        // Merchant name and city have EMVCo max lengths of 25 and 15 chars.
        $merchantName = mb_substr((string) config('khqr.merchant_name'), 0, 25);
        $merchantCity = mb_substr((string) config('khqr.merchant_city'), 0, 15);

        $payload = $this->tlv('00', '01')                                           // Payload Format Indicator
                 . $this->tlv('01', '12')                                           // Dynamic QR (12) vs Static (11)
                 . $this->merchantAccountInfo()                                     // Tag 29
                 . $this->tlv('52', '5999')                                         // Merchant Category Code (general retail)
                 . $this->tlv('53', $currencyCode)                                  // Transaction Currency
                 . $this->tlv('54', number_format($amount, 2, '.', ''))             // Transaction Amount
                 . $this->tlv('58', 'KH')                                           // Country Code
                 . $this->tlv('59', $merchantName)                                  // Merchant Name
                 . $this->tlv('60', $merchantCity)                                  // Merchant City
                 . $this->additionalDataField($billRef);                            // Bill Reference

        // Append CRC tag + 2-char length + 4-char CRC value
        $payload .= '6304';                   // tag + length placeholder
        $payload .= $this->crc16($payload);   // 4 uppercase hex chars

        return $payload;
    }

    /**
     * Compute the MD5 hash of a QR payload.
     * This hash is used as the lookup key in the Bakong Open API.
     */
    public function md5Hash(string $qrString): string
    {
        return md5($qrString);
    }

    // -----------------------------------------------------------------------
    // Bakong Open API — Transaction Verification (free, no auth required)
    // -----------------------------------------------------------------------

    /**
     * Check whether a KHQR transaction has been settled by calling the
     * Bakong Open API endpoint POST /v1/check_transaction_by_md5.
     *
     * @param  string  $md5  MD5 of the original QR payload
     * @return array         Raw Bakong API response
     *                       On success: { "errorCode": 0, "data": {...}, "message": "ok" }
     *                       Not found : { "errorCode": 6, "message": "..." }
     */
    public function checkTransactionByMd5(string $md5): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(config('khqr.bakong_api_url') . '/v1/check_transaction_by_md5', [
                    'md5' => $md5,
                ]);

            return $response->json() ?? ['errorCode' => -1, 'message' => 'Empty response from Bakong API'];
        } catch (\Throwable $e) {
            return ['errorCode' => -1, 'message' => 'Bakong API error: ' . $e->getMessage()];
        }
    }
}
