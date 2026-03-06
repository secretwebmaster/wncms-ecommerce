<?php

return [
    'currency' => 'USD',
    'default_grace_days' => 3,
    'renewal_chunk_size' => 100,
    'processor_namespaces' => [
        'App\\PaymentGateways',
        'Secretwebmaster\\WncmsEcommerce\\PaymentGateways',
    ],
];
