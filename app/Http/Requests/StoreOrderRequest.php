<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() {
        return [
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string|max:50',
            'quantity' => 'sometimes|integer|min:1',
            'message' => 'nullable|string|max:2000'
        ];
    }
}
