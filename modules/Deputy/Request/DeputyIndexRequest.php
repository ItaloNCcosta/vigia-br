<?php

declare(strict_types=1);

namespace Modules\Deputy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Enums\StateEnum;

final class DeputyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'min:2', 'max:100'],
            'state' => ['nullable', 'string', 'size:2', Rule::enum(StateEnum::class)],
            'party' => ['nullable', 'string', 'min:2', 'max:20'],
            'status' => ['nullable', 'string', 'in:Exercício,Afastado,Fim de Mandato'],
            'in_exercise' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'order_by' => ['nullable', 'string', 'in:name,total_expenses,state_code,party_acronym'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'state.size' => 'O estado deve ter exatamente 2 caracteres (UF).',
            'state.enum' => 'Estado inválido.',
            'per_page.max' => 'Máximo de 100 itens por página.',
        ];
    }

    public function filters(): array
    {
        return $this->safe()->only([
            'name',
            'state',
            'party',
            'status',
            'in_exercise',
            'order_by',
            'order',
        ]);
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 20);
    }
}
