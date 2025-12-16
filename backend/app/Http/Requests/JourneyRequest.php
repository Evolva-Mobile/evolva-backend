<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JourneyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'boolean',
            'image_url' => 'nullable|string|url|max:2048'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório.',
            'title.string' => 'O título deve ser uma string.',
            'title.max' => 'O título não pode exceder 255 caracteres.',
            'description.string' => 'A descrição deve ser uma string.',
            'is_private.boolean' => 'O campo de privacidade deve ser verdadeiro ou falso.',
            'image_url.url' => 'A URL da imagem deve ser válida.',
            'image_url.max' => 'A URL da imagem não pode exceder 2048 caracteres.'
        ];
    }
}
