<?php

namespace App\Http\Requests;

use App\Enums\DeliveryMethod;
use App\Enums\ShippingStatus;
use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,processing,completed,cancelled'],
            'shipping_status' => [
                'sometimes',
                'nullable',
                'in:pending,processing,delivering,ready_for_pickup,completed,cancelled',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $order = $this->route('order');
                $shippingStatus = ShippingStatus::tryFrom((string) $this->input('shipping_status'));

                if (! $order instanceof Order || $shippingStatus === null) {
                    return;
                }

                $allowedStatuses = $order->delivery_method === DeliveryMethod::DELIVERY
                    ? ShippingStatus::forDelivery()
                    : ShippingStatus::forPickup();

                if (! in_array($shippingStatus, $allowedStatuses, true)) {
                    $validator->errors()->add(
                        'shipping_status',
                        'The shipping status does not match the order delivery method.'
                    );
                }
            },
        ];
    }
}
