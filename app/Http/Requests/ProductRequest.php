<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'sku'         => 'nullable|string|unique:products,sku,' . $this->id,
            'barcode'     => 'nullable|string|max:255',
            'qr_code'     => 'nullable|string|max:255',
            'image'       => ['nullable', function ($attribute, $value, $fail) {
                if ($this->hasFile('image')) {
                    // Validate if it's a file upload
                    $file = $this->file('image');
                    if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
                        return $fail('The image must be a valid file of type jpeg, png, gif.');
                    }
                } else {
                    // Validate if it's a base64 string
                    if (!preg_match('/^data:image\/(\w+);base64,/', $value, $type)) {
                        return $fail('The image must be a valid base64 string.');
                    }
                    $type = strtolower($type[1]);
                    if (!in_array($type, ['jpeg', 'png', 'gif', 'jpg'])) {
                        return $fail('The image must be of type jpeg, png, gif, or jpg.');
                    }
                }
            }],
            'category_id' => 'nullable|exists:categories,id',
            'weight_in_grams' => 'nullable|numeric|min:0',
            'length_cm'   => 'nullable|numeric|min:0',
            'width_cm'    => 'nullable|numeric|min:0',
            'height_cm'   => 'nullable|numeric|min:0',
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
            //
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
            //
        ];
    }

   

}
