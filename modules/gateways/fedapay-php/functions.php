<?php

require_once __DIR__ . '/init.php';

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
    $systemUrl = $params['systemurl'];
    $moduleName = $params['paymentmethod'];
    $invoiceId = urlencode($params['invoiceid']);
    $returnUrl = urlencode($params['returnurl']);

    return $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php'.
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
            'firstname' => $params['clientdetails']['firstname'],
            'lastname' => $params['clientdetails']['lastname'],
            'email' => $params['clientdetails']['email']
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
