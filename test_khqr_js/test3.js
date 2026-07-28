const { BakongKHQR, khqrData, IndividualInfo } = require('bakong-khqr');

try {
    const individualInfo = new IndividualInfo(
        'liihorr_food@bakong',
        'LIHOR Phon',
        'Phnom Penh',
        {
            currency: khqrData.currency.usd,
            amount: 5.00,
            billNumber: 'BAKONG-123'
        }
    );

    const khqr = BakongKHQR.generateIndividual(individualInfo);
    console.log("Official KHQR:\n", khqr.data.qr);
} catch (e) {
    console.error("Error:", e);
}
