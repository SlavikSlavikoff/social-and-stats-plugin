<?php

namespace Azuriom\Plugin\SocialProfile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:0'],
        ];
    }
}
