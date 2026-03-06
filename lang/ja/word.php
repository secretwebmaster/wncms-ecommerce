<?php

return [
    'ecommerce_payment_gateway_setting' => 'EC決済ゲートウェイ',
    'paypal' => 'PayPal',
    'future_gateways' => '今後のゲートウェイ',

    'ecommerce_paypal_enabled' => 'PayPalを有効化',
    'ecommerce_paypal_mode' => 'PayPalモード',
    'ecommerce_paypal_client_id' => 'PayPal Client ID',
    'ecommerce_paypal_client_secret' => 'PayPal Client Secret',
    'ecommerce_paypal_webhook_id' => 'PayPal Webhook ID',
    'ecommerce_paypal_base_url' => 'PayPal API Base URL',

    'ecommerce_stripe_api_key' => 'Stripe APIキー',
    'ecommerce_stripchat_api_key' => 'Stripchat APIキー',
    'ecommerce_epusdt_api_key' => 'EPUSDT APIキー',
    'pending' => '保留中',
    'succeeded' => '成功',
    'completed' => '完了',
    'failed' => '失敗',
    'refunded' => '返金済み',
    'cancelled' => 'キャンセル',
    'ecpay_gateway_credentials_required' => 'ECPay ゲートウェイには MerchantID・HashKey・HashIV が必要です。',
    'ecpay_checkout_start_failed' => 'ECPay チェックアウトの開始に失敗しました。',
    'ecpay_credential_mapping_hint' => 'ECPay マッピング: client_id=MerchantID、client_secret=HashKey、webhook_secret=HashIV。',
    'webhook_secret_or_id' => 'Webhook Secret / ID',
    'invalid_return_url' => 'Return URL は絶対 URL か / で始まるパスである必要があります',
];
