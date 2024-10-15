<?php


namespace App\Repositories;

use App\Models\Call;

interface CallRepositoryInterface
{
    public function getAllCalls();
    public function getCallById($id);
    public function saveCall(array $data);
    public function updateCall(array $data);
    public function saveForwardCallDetail(string $dialerCallSid, string $forwardNumber);
    public function saveForwardCallSid(string $dialerCallSid, string $forwradCallSid);
}
