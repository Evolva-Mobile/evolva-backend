<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $userToUpdate = $this->route('user');

        return $this->user()->can('update', $userToUpdate);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            // 'sometimes' significa que o campo só é validado se estiver presente.
            'name' => 'sometimes|string|max:255',

            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],

            'password' => [
                'sometimes',
                'confirmed',
                Password::min(8)
            ],
            'avatar_url' => 'sometimes|nullable|string|url|max:2048',
        ];
    }
}
