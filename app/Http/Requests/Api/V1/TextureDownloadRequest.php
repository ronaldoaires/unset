<?php

namespace App\Http\Requests\Api\V1;

use App\Models\TextureMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TextureDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution' => ['nullable', Rule::in(TextureMap::RESOLUTIONS)],
        ];
    }
}
