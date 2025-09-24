<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest {
    public function authorize() {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules() {
        return [
            'slug' => 'required|string|unique:products,slug',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'currency' => 'nullable|string|max:10',
            'condition' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'nullable|integer',
            'image_path' => 'nullable|string'
        ];
    }
}
