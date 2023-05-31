<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileIndex extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'role'          => $this->role,
            'status'        => $this->status,         
            'exp'           => $this->exp,
            'avatar'        => $this->avatar,
            'referrer'      => $this->referrer,
            'ref_code'      => $this->ref_code,
            'email'         => $this->email,      
            'game'          => $this->game,
            'mmr'           => $this->mmr,
            'roles'         => $this->roles,      
            'created_at'    => $this->created_at           
        ];
    }
}
