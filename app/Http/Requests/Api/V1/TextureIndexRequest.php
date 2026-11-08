<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Texture;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TextureIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Same policy as FamilyIndexRequest: bad values answer 422. */
    public function rules(): array
    {
        return [
            'q'             => ['nullable', 'string', 'max:100'],
            'category'      => ['nullable', 'string', 'max:120'],
            'material_type' => ['nullable', Rule::in(array_keys(Texture::MATERIAL_TYPES))],
            'free'          => ['nullable', 'boolean'],
            'featured'      => ['nullable', 'boolean'],
            'sort'          => ['nullable', Rule::in(['recent', 'downloads', 'name'])],
            'per_page'      => ['nullable', 'integer', 'between:1,50'],
            'page'          => ['nullable', 'integer', 'min:1'],
        ];
    }
}
