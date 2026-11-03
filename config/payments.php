<?php

$production = (bool) env('MIDTRANS_IS_PRODUCTION', false);
$prefix = $production ? 'MIDTRANS_PRODUCTION_' : 'MIDTRANS_SANDBOX_';
$paymentTimeoutMinutes = max(5, min(10080, (int) env('PAYMENT_TIMEOUT_MINUTES', 180)));

return [
    'timeout_minutes' => $paymentTimeoutMinutes,
    'midtrans' => [
        'enabled' => (bool) env('MIDTRANS_ENABLED', false),
        'is_production' => $production,
        'merchant_id' => env($prefix.'MERCHANT_ID') ?: env('MIDTRANS_MERCHANT_ID', ''),
        'server_key' => env($prefix.'SERVER_KEY') ?: env('MIDTRANS_SERVER_KEY', ''),
        'client_key' => env($prefix.'CLIENT_KEY') ?: env('MIDTRANS_CLIENT_KEY', ''),
        'expiry_minutes' => $paymentTimeoutMinutes,
        'snap_script_url' => $production ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js',
    ],
    'manual' => [
        'bca' => ['provider' => 'BCA', 'destination_number' => env('MANUAL_PAYMENT_BCA_ACCOUNT', '12345'), 'account_name' => env('MANUAL_PAYMENT_ACCOUNT_NAME', 'Bunkue Pastry')],
        'dana' => ['provider' => 'DANA', 'destination_number' => env('MANUAL_PAYMENT_DANA_NUMBER', '12345'), 'account_name' => env('MANUAL_PAYMENT_ACCOUNT_NAME', 'Bunkue Pastry')],
        'gopay' => ['provider' => 'GOPAY', 'destination_number' => env('MANUAL_PAYMENT_GOPAY_NUMBER', '12345'), 'account_name' => env('MANUAL_PAYMENT_ACCOUNT_NAME', 'Bunkue Pastry')],
    ],
    'proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),
    'proof_max_kb' => (int) env('PAYMENT_PROOF_MAX_KB', 5120),
];
