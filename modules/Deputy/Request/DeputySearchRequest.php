<?php

declare(strict_types=1);

namespace Modules\Deputy\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeputySearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Digite um termo de busca.',
            'q.min' => 'Digite pelo menos 2 caracteres.',
            'q.max' => 'O termo de busca é muito longo.',
            'limit.max' => 'Máximo de 50 resultados.',
        ];
    }

    /**
     * Get the search query.
     */
    public function searchQuery(): string
    {
        return $this->validated('q');
    }

    /**
     * Get the limit.
     */
    public function limit(): int
    {
        return (int) $this->validated('limit', 10);
    }
}
