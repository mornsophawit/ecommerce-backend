<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bakong Account
    |--------------------------------------------------------------------------
    | Your Bakong merchant/personal account in the format:
    |   <phone_or_id>@<bank_identifier>
    |
    | Examples:
    |   "012345678@aclb"   — ACLEDA Bank
    |   "012345678@wing"   — Wing Bank
    |   "012345678@campu"  — Cambodia Post Bank
    |   "012345678@aba"    — ABA Bank
    |
    | Register at https://bakong.nbc.org.kh to get your Bakong account.
    */
    'bakong_account' => env('KHQR_BAKONG_ACCOUNT', ''),

    /*
    |--------------------------------------------------------------------------
    | Merchant Details
    |--------------------------------------------------------------------------
    | These values appear in the QR code and are visible to the payer.
    | merchant_name : max 25 characters
    | merchant_city : max 15 characters
    */
    'merchant_name' => env('KHQR_MERCHANT_NAME', 'My Store'),
    'merchant_city' => env('KHQR_MERCHANT_CITY', 'Phnom Penh'),

    /*
    |--------------------------------------------------------------------------
    | Optional Merchant / Acquirer IDs
    |--------------------------------------------------------------------------
    | Leave null if you do not have a registered merchant ID or acquirer.
    */
    'merchant_id'  => env('KHQR_MERCHANT_ID', null),
    'acquirer_id'  => env('KHQR_ACQUIRER_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Bakong Open API
    |--------------------------------------------------------------------------
    | Free endpoint provided by the National Bank of Cambodia to verify
    | whether a KHQR transaction has been settled.
    | No authentication token is required for check_transaction_by_md5.
    */
    'bakong_api_url' => env('KHQR_BAKONG_API_URL', 'https://api-bakong.nbc.org.kh'),

];
