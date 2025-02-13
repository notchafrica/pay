<?php

declare(strict_types=1);

namespace Notch\Framework\Services;

use Spatie\WebhookServer\WebhookCall;

final class WebhookService
{
    public static function send(string $url, $business, $data, $secret = null): void
    {

        if ($url) {
            WebhookCall::create()
                ->url($url)
                ->payload($data)
                ->withHeaders([
                    'x-notch-signature' => self::generateSignature($data, $secret ?? ''),
                ])
                ->meta([
                    'business_id' => $business->id,
                    'event' => $data['event'] ?? '',
                    'id' => $data['id'] ?? '',
                    'sandbox' => ! $business->live,
                ])
                ->doNotVerifySsl()
                ->doNotSign()
                ->dispatch();
        }
    }

    public static function generateSignature($data, $secret = null)
    {

        $payloadJson = json_encode($data);
        $signature = hash_hmac('sha256', $payloadJson, $secret);

        return $signature;
    }
}
