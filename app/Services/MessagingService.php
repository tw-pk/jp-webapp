<?php

namespace App\Services;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\TwilioConversationResource;
use App\Interfaces\MessageInterface;
use App\Repositories\ConversationRepository;
use Random\RandomException;
use Twilio\Exceptions\TwilioException;

class MessagingService implements MessageInterface
{
    protected ConversationRepository $conversationRepository;

    /**
     * @param ConversationRepository $conversationRepository
     */
    public function __construct(ConversationRepository $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;
    }


    /**
     * @throws TwilioException
     * @throws RandomException
     */
    public function allConversations($filters, $perPage=null): TwilioConversationResource
    {
        return new TwilioConversationResource($this->conversationRepository->getConversations($filters, $perPage));
    }

    public function createConversation($to, $from, $user_id)
    {

    }

    public function findConversationById($id)
    {
        // TODO: Implement findConversationById() method.
    }

    public function deleteConversation($id)
    {
        // TODO: Implement deleteConversation() method.
    }

    public function getConversationMessages($id)
    {
        return new ConversationResource($this->conversationRepository->getConversationMessages($id));
    }

    public function updateConversation()
    {
        // TODO: Implement updateConversation() method.
    }
}
