<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level middleware restricts this to logistics/admin already.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_matches' => ['required', 'boolean'],
            // PIN is only required when the driver confirms the item matches.
            'pin' => ['nullable', 'required_if:item_matches,1', 'digits:4'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function itemMatches(): bool
    {
        return $this->boolean('item_matches');
    }
}
