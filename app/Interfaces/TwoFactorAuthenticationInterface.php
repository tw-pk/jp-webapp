<?php

namespace App\Interfaces;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface TwoFactorAuthenticationInterface
{

    function getCodeExpiry(): string;

    function setCodeExpiry($time): void;
    function generateCode(string $phoneNumber, string $channel, int $expiryTime = 10): JsonResponse;

    function verifyCode(string $serviceSid, string $to, int $code): JsonResponse;

    function isSessionVerified(Request $request): JsonResponse;

}
