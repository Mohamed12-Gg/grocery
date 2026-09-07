<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatbotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
                    'id' => $this['id'],
                    'conversation_id' => $this['conversation_id'],
                    'session_id' => $this['conversation_id'],  // backwards compatibility
                    'question' => $this['question'],
                    'answer' => $this['answer'],
                    'rating' => $this['rating'],
                ];
    }
}
