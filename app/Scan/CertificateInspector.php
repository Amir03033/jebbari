<?php

namespace App\Scan;

/**
 * Reads the TLS certificate of a host: is it valid for this name and when
 * does it expire. Connects to the address the UrlGuard approved.
 */
class CertificateInspector
{
    /** @return array{valid: bool, days_left: ?int}|null null when no TLS connection could be made */
    public function inspect(string $host, string $ip): ?array
    {
        $verified = $this->read($host, $ip, true);

        if ($verified !== null) {
            return ['valid' => true, 'days_left' => $verified];
        }

        $unverified = $this->read($host, $ip, false);

        return $unverified === null && ! $this->reachable($host, $ip) ? null : ['valid' => false, 'days_left' => $unverified];
    }

    private function read(string $host, string $ip, bool $verify): ?int
    {
        $context = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => $verify,
            'verify_peer_name' => $verify,
            'allow_self_signed' => ! $verify,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ]]);

        $address = str_contains($ip, ':') ? '['.$ip.']' : $ip;
        $socket = @stream_socket_client('ssl://'.$address.':443', $errno, $error, (int) config('scan.connect_timeout'), STREAM_CLIENT_CONNECT, $context);

        if (! $socket) {
            return null;
        }

        $params = stream_context_get_params($socket);
        fclose($socket);
        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        $info = $cert ? openssl_x509_parse($cert) : false;

        if (! $info || ! isset($info['validTo_time_t'])) {
            return null;
        }

        return (int) floor(($info['validTo_time_t'] - time()) / 86400);
    }

    private function reachable(string $host, string $ip): bool
    {
        $address = str_contains($ip, ':') ? '['.$ip.']' : $ip;
        $socket = @stream_socket_client('tcp://'.$address.':443', $errno, $error, (int) config('scan.connect_timeout'));

        if ($socket) {
            fclose($socket);

            return true;
        }

        return false;
    }
}
