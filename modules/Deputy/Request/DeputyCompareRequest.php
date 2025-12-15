<?php

declare(strict_types=1);

namespace Modules\Deputy\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeputyCompareRequest extends FormRequest
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
            'deputy1' => ['required', 'uuid', 'exists:deputies,id'],
            'deputy2' => ['required', 'uuid', 'exists:deputies,id', 'different:deputy1'],
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
            'deputy1.required' => 'Selecione o primeiro deputado.',
            'deputy1.uuid' => 'ID do primeiro deputado inválido.',
            'deputy1.exists' => 'Primeiro deputado não encontrado.',
            'deputy2.required' => 'Selecione o segundo deputado.',
            'deputy2.uuid' => 'ID do segundo deputado inválido.',
            'deputy2.exists' => 'Segundo deputado não encontrado.',
            'deputy2.different' => 'Selecione dois deputados diferentes.',
        ];
    }
}
