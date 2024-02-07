<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TwilioConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'chatsContacts' => $this->resource['chatContacts'],
            'contacts' => $this->resource['contacts'],
            'profileUser' => $this->resource['profileUser'],
        ];
    }
}
