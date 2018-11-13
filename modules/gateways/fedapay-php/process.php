<?php
/**
 * FedaPay Payment Callback File
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../fedapay-php/functions.php';

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables('fedapay');

// Die if module is not active.
if (!$gatewayParams['type']) {
    die('Module Not Activated');
}

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$params = decodeParams($_POST);

if (!verifyCSRFToken($params['token'])) {
    die('CSRF Token not valid');
    exit;
}

$invoiceId = $params['invoice_id'];

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

setup_fedapay_gateway($gatewayParams);

try {
    try {
        $url = create_fedapay_transaction($params);
        header('Location: ' . $url);
    } catch (\Exception $e) {
        die( display_fedapay_errors($e));
    }
} catch(\Exception $e) {
    die(display_fedapay_errors($e));
}

exit;
