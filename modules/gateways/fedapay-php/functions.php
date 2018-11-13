<?php

require_once __DIR__ . '/init.php';

/**
 * Generate a random string
 * @param string $length
 * @return string
 */
function randomString( $length = 20 ) {
    $seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijqlmnopqrtsuvwxyz0123456789';
    $max = strlen( $seed ) - 1;
    $string = '';

    for ( $i = 0; $i < $length; ++$i ) {
        $string .= $seed{intval( mt_rand( 0.0, $max ) )};
    }

    return $string;
}

/**
 * Generate CSRFToken
 * @param string $key
 * @return string
 */
function generateCSRFToken( $key ) {
    // token generation (basically base64_encode any random complex string, time() is used for token expiration)
    $token = base64_encode( time() . $extra . randomString( 32 ) );
    // store the one-time token in session
    $_SESSION[ 'feda_csrf_' . $key ] = $token;
    return $token;
}

/**
 * Verify CSRFToken
 * @param string $key
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($key, $token) {
    // Get valid token from session
    $hash = $_SESSION[ 'feda_csrf_' . $key ];
    // Check if session token matches form token
    if ( $token != $hash ) return false;

    return true;
}

/**
 * Encode params
 * @param array $params
 * @return array
 */
function encodeParams($params)
{
    foreach ($params as $k => $v) {
        $params[$k] = urlencode($v);
    }

    return $params;
}

/**
 * Decode params
 * @param array $params
 * @return array
 */
function decodeParams($params)
{
    foreach ($params as $k => $v) {
        $params[$k] = urldecode($v);
    }

    return $params;
}

/**
 * Setup FedaPay Gateway
 */
function setup_fedapay_gateway($params)
{
    // Gateway Configuration Parameters
    $liveSecretKey = $params['liveSecretKey'];
    $sandboxMode = $params['sandboxMode'];
    $sandboxSecretKey = $params['sandboxSecretKey'];

    if ($sandboxMode == 'on') {
        \FedaPay\FedaPay::setApiKey($sandboxSecretKey);
        \FedaPay\FedaPay::setEnvironment('sandbox');
    } else {
        \FedaPay\FedaPay::setApiKey($liveSecretKey);
        \FedaPay\FedaPay::setEnvironment('live');
    }
}

/**
 * Return transaction callback
 * @param array $params
 * @return string
 */
function transaction_callback_url($params)
{
    $systemUrl = $params['system_url'];
    $moduleName = $params['payment_method'];
    $invoiceId = $params['invoice_id'];
    $returnUrl = urldecode($params['return_url']);

    return $systemUrl . 'modules/gateways/callback/' . $moduleName . '.php'.
        '?invoice_id=' . $invoiceId . '&return_url=' . $returnUrl;
}

/**
 * Return an integer amount formated to fedapay way
 * @param string $amount
 * @param string $currency
 * @return integer
 */
function to_fedapay_amount($amount, $currency)
{
    $object = \FedaPay\Currency::all();

    foreach ($currencies as $c) {
        if ($c->iso == $currency) {
            $amount = $amount * $c->div;
        }
    }

    return ceil($amount);
}

/**
 * Return fedapay transaction params
 * @param array $params
 * @return array
 */
function fedapay_transaction_params($params)
{
    return array(
        'description' => $params["description"],
        'amount' => to_fedapay_amount($params['amount'], $params['currency']),
        'currency' => ['iso' => $params['currency']],
        'callback_url' => transaction_callback_url($params),
        'customer' => array(
            'firstname' => $params['firstname'],
            'lastname' => $params['lastname'],
            'email' => urldecode($params['email']),
            'phone_number' => array(
                'country' => $params['country'],
                'number' => $params['phone']
            )
        )
    );
}

/**
 * Create transaction
 * @param array $params
 * @return string the payment link
 */
function create_fedapay_transaction($params)
{
    $transaction = \FedaPay\Transaction::create(
        fedapay_transaction_params($params)
    );

    sleep(3); // For some reason, server failed. Wait 3s

    $token = $transaction->generateToken();

    return $token->url;
}

/**
 * Retrieve transaction
 * @param string $id
 * @return \Fedapay\Transaction
 */
function retrieve_fedapay_transaction($id)
{
    return \FedaPay\Transaction::retrieve($id);
}

/**
 * Format and return fedapay errors
 * @return string
 */
function display_fedapay_errors(\Exception $e)
{
    $message = $e->getMessage();

    if ($e instanceof \FedaPay\Error\ApiConnection && $e->hasErrors()) {
        $message .= '<ul>';

        foreach ($e->getErrors() as $n => $errors) {
            foreach ($errors as $err) {
                $message .= "<li>$n: $err</li>";
            }
        }

        $message .= '</ul>';
    }

    return $message;
}

function transaction_return_url($returnUrl, $success)
{
    return $returnUrl . (
        $success ? "&paymentsuccess=true" : "&paymentfailed=true"
    );
}
