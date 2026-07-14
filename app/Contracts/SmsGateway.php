<?php

namespace App\Contracts;

interface SmsGateway
{
    /**
     * @return array{success: bool, provider_response: ?string}
     */
    public function send(string $to, string $message): array;
}
