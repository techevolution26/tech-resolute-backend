<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @method mixed input(string $key = null, mixed $default = null)
 */
class StoreSellerApplicationRequest extends FormRequest
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
        // Get the application type, defaulting to 'business' if not provided.
        // For a 'store' request, 'application_type' should typically be required,
        // but if 'sometimes' is desired, ensure the default here matches expected behavior.
        $applicationType = $this->input('application_type', 'business');

        $rules = [
            'application_type' => ['required', 'in:business,one_time'], // Ensure this is always present and valid
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'], // Adjusted max to 50 for consistency
            'website' => ['nullable', 'url', 'max:255'], // Added max for consistency
            'message' => ['nullable', 'string', 'max:2000'], // Added max for consistency
            'logo_url' => ['nullable', 'string', 'max:1024'], // Added max for consistency
        ];

        if ($applicationType === 'business') {
            $rules['business_name'] = ['required', 'string', 'max:255'];
            // For 'business' type, 'items' should not be present or should be empty/ignored
            $rules['items'] = ['nullable', 'array', 'max:0']; // Ensure items array is not provided or empty
        } else { // 'one_time'
            $rules['business_name'] = ['nullable', 'string', 'max:255']; // Optional for 'one_time'
            $rules['items'] = ['required', 'array', 'min:1']; // Items required for 'one_time'
            $rules['items.*.title'] = ['required', 'string', 'max:255'];
            $rules['items.*.condition'] = ['nullable', 'string', 'max:255'];
            $rules['items.*.quantity'] = ['nullable', 'integer', 'min:1']; // Consider making this required if items are required
            $rules['items.*.estimated_price'] = ['nullable', 'string', 'max:50'];
            $rules['items.*.description'] = ['nullable', 'string', 'max:2000'];
            $rules['items.*.image_url'] = ['nullable', 'url', 'max:1024']; // Added max for consistency
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'application_type.required' => 'The application type is required.',
            'application_type.in' => 'The application type must be either "business" or "one_time".',
            'business_name.required' => 'The business name is required for business applications.',
            'email.required' => 'Your contact email is required so we can respond.',
            'email.email' => 'Please provide a valid email address.',
            'items.required' => 'At least one item is required for one-time applications.',
            'items.min' => 'At least one item is required for one-time applications.',
            'items.*.title.required' => 'Item title is required.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
        ];
    }
}
