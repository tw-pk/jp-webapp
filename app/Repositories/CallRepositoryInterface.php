<?php


namespace App\Repositories;

use App\Models\Call;

interface CallRepositoryInterface
{
    public function getAllCalls();
    public function getCallById($id);
    public function saveCall(array $data);
}
