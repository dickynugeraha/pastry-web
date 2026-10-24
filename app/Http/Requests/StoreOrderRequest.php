<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_channel' => ['required', Rule::in(config('payments.midtrans.enabled') && filled(config('payments.midtrans.server_key')) && filled(config('payments.midtrans.client_key')) ? ['manual', 'midtrans'] : ['manual'])],
            'manual_payment_method' => ['required_if:payment_channel,manual', 'prohibited_unless:payment_channel,manual', 'nullable', 'in:bca,dana,gopay'],
            'delivery_method' => ['bail', 'required', 'in:delivery,pickup'],
            'recipient_name' => [
                'required_if:delivery_method,delivery',
                'nullable',
                'string',
                'min:2',
                'max:255',
            ],
            'recipient_phone' => [
                'required_if:delivery_method,delivery',
                'nullable',
                'string',
                'regex:/^[0-9+\\-\\s()]{7,30}$/',
            ],
            'shipping_address' => [
                'required_if:delivery_method,delivery',
                'nullable',
                'string',
                'min:5',
                'max:2000',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
