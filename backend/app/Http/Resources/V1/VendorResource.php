<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'vendor_code' => $this->vendor_code,
            'name' => $this->name,
            'business_name' => $this->business_name,
            'vendor_category_id' => $this->vendor_category_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'tax_number' => $this->tax_number,
            'rating' => $this->rating,
            'services_offered' => $this->services_offered,
            'payment_terms' => $this->payment_terms,
            'status' => $this->status,
            'category' => $this->whenLoaded('category'),
            'created_at' => $this->created_at,
        ];
    }
}
