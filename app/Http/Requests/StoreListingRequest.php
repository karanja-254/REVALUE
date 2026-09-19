<?php

namespace App\Http\Requests;

use App\Models\Listing;
use App\Support\ListingOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->wantsJson()) {
            return [
                'type' => ['required', Rule::in(Listing::TYPES)],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            ];
        }

        return [
            'type' => ['required', Rule::in(Listing::TYPES)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(ListingOptions::CATEGORIES)],
            'condition' => ['required', Rule::in(ListingOptions::CONDITIONS)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
