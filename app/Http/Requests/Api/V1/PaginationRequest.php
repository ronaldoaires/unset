<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Page parameters of the simple API lists (favorites, download history).
 */
class PaginationRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 20;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'between:1,50'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
