<?php

namespace App\Http\Requests;

use App\Support\ListingOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCharityNeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->user()?->organization;

        return $organization !== null
            && $organization->isVerified()
            && $organization->isCharity();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(ListingOptions::CATEGORIES)],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
