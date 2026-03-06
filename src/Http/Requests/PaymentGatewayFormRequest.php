<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentGatewayFormRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('id');

        return [
            'status' => 'required|in:active,inactive',
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('payment_gateways', 'slug')->ignore($id),
            ],
            'type' => 'required|in:redirect,inline',
            'driver' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\\\-]+$/'],
            'account_id' => 'nullable|string|max:255',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string|max:255',
            'endpoint' => 'nullable|url|max:255',
            'return_url' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                $raw = trim((string) $value);
                if ($raw === '') {
                    return;
                }

                if (str_starts_with($raw, '/')) {
                    return;
                }

                $urlForValidation = strtr($raw, [
                    '{order_slug}' => 'order-slug',
                    '{order_id}' => '123',
                    '{gateway_slug}' => 'gateway-slug',
                ]);

                if (!filter_var($urlForValidation, FILTER_VALIDATE_URL)) {
                    $fail(__('wncms-ecommerce::word.invalid_return_url'));
                }
            }],
            'currency' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z]{3,10}$/'],
            'is_sandbox' => 'nullable|boolean',
            'attributes' => 'nullable|array',
            'attributes.*.key' => 'nullable|string|max:255',
            'attributes.*.value' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
        ];
    }
}
