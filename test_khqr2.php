<?php

function makeTag($id, $value)
{
    return sprintf('%02d%02d%s', $id, strlen($value), $value);
}

function crc16_checksum($str)
{
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($str); $i++) {
        $x = (($crc >> 8) ^ ord($str[$i])) & 0xFF;
        $x ^= $x >> 4;
        $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ ($x << 0)) & 0xFFFF;
    }
    return sprintf('%04X', $crc);
}

function generateKHQRString($accountId, $merchantName, $amount, $tranId)
{
    $payload = '';
    $payload .= makeTag(0, '01'); // Payload Format Indicator
    $payload .= makeTag(1, '12'); // Dynamic QR (12)
    
    // Tag 29: Merchant Account Info
    $tag29Value = '';
    // Typically KHQR uses bakong.nbc.gov.kh for GUID
    $tag29Value .= makeTag(0, 'bakong.nbc.gov.kh'); 
    $tag29Value .= makeTag(1, $accountId);
    
    // Subtag 02 is often the merchant account acquiring bank, not just 'bakong'
    // but let's see.
    $parts = explode('@', $accountId);
    if (count($parts) > 1) {
        $tag29Value .= makeTag(2, $parts[1]); // Subtag 02
    }
    
    $payload .= makeTag(29, $tag29Value);
    $payload .= makeTag(52, '5999'); // Category: Food
    $payload .= makeTag(53, '840'); // Currency: USD (840)
    $payload .= makeTag(54, number_format($amount, 2, '.', ''));
    $payload .= makeTag(58, 'KH'); // Country Code
    $payload .= makeTag(59, substr($merchantName, 0, 25)); // Merchant Name
    $payload .= makeTag(60, 'Phnom Penh'); // City
    
    // Tag 62: Additional Data (Bill ID)
    if ($tranId) {
        $tag62Value = makeTag(1, $tranId); // 01 is Bill Number
        $payload .= makeTag(62, $tag62Value);
    }
    
    // Tag 63: CRC16 Checksum
    $payload .= '6304';
    $checksum = crc16_checksum($payload);
    
    return $payload . $checksum;
}

$qr = generateKHQRString('liihorr_food@bakong', 'LIHOR Phon', 5.00, 'BAKONG-123');
echo "Generated QR (bakong.nbc.gov.kh):\n";
echo $qr . "\n\n";

function generateKHQRString2($accountId, $merchantName, $amount, $tranId)
{
    $payload = '';
    $payload .= makeTag(0, '01'); // Payload Format Indicator
    $payload .= makeTag(1, '12'); // Dynamic QR (12)
    
    // Tag 30 for Bakong?
    $tag30Value = '';
    $tag30Value .= makeTag(0, 'kh.gov.nbc.bakong'); 
    $tag30Value .= makeTag(1, $accountId);
    
    $payload .= makeTag(30, $tag30Value);
    $payload .= makeTag(52, '5999'); // Category: Food
    $payload .= makeTag(53, '840'); // Currency: USD (840)
    $payload .= makeTag(54, number_format($amount, 2, '.', ''));
    $payload .= makeTag(58, 'KH'); // Country Code
    $payload .= makeTag(59, substr($merchantName, 0, 25)); // Merchant Name
    $payload .= makeTag(60, 'Phnom Penh'); // City
    
    // Tag 62: Additional Data (Bill ID)
    if ($tranId) {
        $tag62Value = makeTag(1, $tranId);
        $payload .= makeTag(62, $tag62Value);
    }
    
    // Tag 63: CRC16 Checksum
    $payload .= '6304';
    $checksum = crc16_checksum($payload);
    
    return $payload . $checksum;
}
$qr2 = generateKHQRString2('liihorr_food@bakong', 'LIHOR Phon', 5.00, 'BAKONG-123');
echo "Generated QR 2 (kh.gov.nbc.bakong):\n";
echo $qr2 . "\n\n";

