<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only allow access if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route('id') ?? $this->get('id'); // Get the user ID for update operations

        return [
            'email'    => 'required|email|unique:users,email' . ($id ? ",$id" : ''),
            'name'     => 'required|string|max:255',
            'password' => $this->isMethod('POST') ? 'required|min:8|confirmed' : 'nullable|min:8|confirmed',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'email'    => 'email address',
            'name'     => 'full name',
            'password' => 'password',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.required'    => 'The :attribute is required.',
            'email.unique'      => 'The :attribute has already been taken.',
            'name.required'     => 'The :attribute is required.',
            'password.required' => 'The :attribute is required when creating a new user.',
            'password.confirmed' => 'The :attribute confirmation does not match.',
        ];
    }
}
