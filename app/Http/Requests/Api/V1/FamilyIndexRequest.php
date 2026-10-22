<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Unlike the site listing (which ignores bad values), the API answers 422
     * so client bugs show up during development.
     */
    public function rules(): array
    {
        return [
            'q'             => ['nullable', 'string', 'max:100'],
            // A subcategory slug is only unique inside its category.
            'category'      => ['nullable', 'string', 'max:100', 'required_with:subcategory'],
            'subcategory'   => ['nullable', 'string', 'max:100'],
            'brand'         => ['nullable', 'string', 'max:100'],
            'free'          => ['nullable', 'boolean'],
            'revit_version' => ['nullable', 'integer', 'between:2000,2100'],
            'sort'          => ['nullable', Rule::in(['recent', 'downloads', 'views', 'name'])],
            'per_page'      => ['nullable', 'integer', 'between:1,50'],
            'page'          => ['nullable', 'integer', 'min:1'],
        ];
    }
}