<?php

return [
    'ecommerce_payment_gateway_setting' => '电商支付网关',
    'paypal' => 'PayPal',
    'future_gateways' => '后续网关',

    'ecommerce_paypal_enabled' => '启用 PayPal',
    'ecommerce_paypal_mode' => 'PayPal 模式',
    'ecommerce_paypal_client_id' => 'PayPal Client ID',
    'ecommerce_paypal_client_secret' => 'PayPal Client Secret',
    'ecommerce_paypal_webhook_id' => 'PayPal Webhook ID',
    'ecommerce_paypal_base_url' => 'PayPal API Base URL',

    'ecommerce_stripe_api_key' => 'Stripe API Key',
    'ecommerce_stripchat_api_key' => 'Stripchat API Key',
    'ecommerce_epusdt_api_key' => 'EPUSDT API Key',
    'ecpay_gateway_credentials_required' => 'ECPay 支付网关需要 MerchantID、HashKey 与 HashIV。',
    'ecpay_checkout_start_failed' => 'ECPay 结账启动失败。',
    'ecpay_credential_mapping_hint' => 'ECPay 字段映射：client_id=MerchantID、client_secret=HashKey、webhook_secret=HashIV。',
    'webhook_secret_or_id' => 'Webhook Secret / ID',
    'invalid_return_url' => '回传网址必须是完整 URL 或以 / 开头的路径',
];
