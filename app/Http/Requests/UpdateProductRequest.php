<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest {
    public function authorize() {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules() {
        $id = $this->route( 'product' ) ?? $this->route( 'id' );
        return [
            'slug' => 'sometimes|string|unique:products,slug,' . $id,
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'currency' => 'nullable|string|max:10',
            'condition' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'nullable|integer',
            'image_path' => 'nullable|string'
        ];
    }
}
