<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpdateProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'email' => $this->email,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'preferred_languages' => $this->preferred_languages ?? [],
            'profile_image_url' => $this->profile_image_url,
            'updated_at' => $this->updated_at,
        ];
    }
}
