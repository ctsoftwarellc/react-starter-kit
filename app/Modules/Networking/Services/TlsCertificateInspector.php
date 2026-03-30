<?php

namespace App\Modules\Networking\Services;

class TlsCertificateInspector
{
    public function inspect(string $hostname): ?array
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client(
            sprintf('ssl://%s:443', $hostname),
            $errorCode,
            $errorMessage,
            5,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($client === false) {
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;

        if ($certificate === null) {
            return null;
        }

        $parsed = openssl_x509_parse($certificate);

        if ($parsed === false) {
            return null;
        }

        return [
            'issued_at' => isset($parsed['validFrom_time_t']) ? now()->createFromTimestamp($parsed['validFrom_time_t']) : null,
            'expires_at' => isset($parsed['validTo_time_t']) ? now()->createFromTimestamp($parsed['validTo_time_t']) : null,
        ];
    }
}
