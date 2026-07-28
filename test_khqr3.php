<?php
require 'vendor/autoload.php';
use KHQR\BakongKHQR;
use KHQR\Models\IndividualInfo;
use KHQR\Helpers\KHQRData;

try {
    $tranId = 'BAKONG-123-'.time();
    $individualInfo = new IndividualInfo(
        bakongAccountID:     'liihorr_food@bakong',
        merchantName:        'LIHOR Phon',
        merchantCity:        'Phnom Penh',
        currency:            KHQRData::CURRENCY_USD,
        amount:              5.00,
        billNumber:          $tranId,
        storeLabel:          'STORE',
        terminalLabel:       'T1',
        expirationTimestamp: strval(
            (int) floor(microtime(true) * 1000) + (10 * 60 * 1000)
        )
    );

    $res = BakongKHQR::generateIndividual($individualInfo);
    print_r($res);
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
} catch (\TypeError $e) {
    echo "TypeError: " . $e->getMessage() . "\n";
}
