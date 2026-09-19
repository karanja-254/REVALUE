<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
        return [
            'accurate_description' => ['required', 'integer', 'min:1', 'max:5'],
            'smooth_delivery' => ['required', 'integer', 'min:1', 'max:5'],
            'professional_handling' => ['required', 'integer', 'min:1', 'max:5'],
            'punctual_pickup' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
