<?php

namespace App\Interfaces;

interface MessageInterface
{

    public function allConversations($filters, $perPage);

    public function createConversation($to, $from, $user_id);

    public function findConversationById($id);

    public function deleteConversation($id);

    public function getConversationMessages($id);

    public function updateConversation();
}
