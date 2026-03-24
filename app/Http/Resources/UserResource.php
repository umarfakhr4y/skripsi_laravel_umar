<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $profileData = $this->role === 'peserta' ? $this->peserta : $this->mentor;

        // Jika dia mentor, kita sertakan data divisinya
        

        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'role'       => $this->role,
            'data'       => $profileData,
        ];
    }
}
