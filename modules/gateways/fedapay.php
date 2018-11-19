<?php
/**
 * FedaPay Payment Gateway Module
 * Version 0.1.1
 * Payment Gateway modules allow you to integrate payment solutions with the
 * WHMCS platform.
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) FedaPay 2018
 * @license https://www.fedapay.com/license/
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/fedapay-php/functions.php';

if (!function_exists('fedapay_MetaData')) {
    /**
     * Define module related meta data.
     *
     * Values returned here are used to determine module related capabilities and
     * settings.
     *
     * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
     *
     * @return array
     */
    function fedapay_MetaData()
    {
        return array(
            'DisplayName' => 'FedaPay Payment Gateway Module',
            'APIVersion' => '1.1', // Use API Version 1.1
            'DisableLocalCredtCardInput' => true,
            'TokenisedStorage' => false,
        );
    }
}

if (!function_exists('fedapay_config')) {
    /**
     * Define gateway configuration options.
     *
     * The fields you define here determine the configuration options that are
     * presented to administrator users when activating and configuring your
     * payment gateway module for use.
     *
     * Supported field types include:
     * * text
     * * password
     * * yesno
     * * dropdown
     * * radio
     * * textarea
     *
     * Examples of each field type and their possible configuration parameters are
     * provided in the sample function below.
     *
     * @return array
     */
    function fedapay_config()
    {
        return array(
            // the friendly display name for a payment gateway should be
            // defined here for backwards compatibility
            'FriendlyName' => array(
                'Type' => 'System',
                'Value' => 'FedaPay',
            ),
            // The live secret key
            'liveSecretKey' => array(
                'FriendlyName' => 'Live Secret Key',
                'Type' => 'password',
                'Default' => '',
                'Description' => 'Enter your live secret key here',
            ),
            // Enable sandbox mode
            'sandboxMode' => array(
                'FriendlyName' => 'Sandbox Mode',
                'Type' => 'yesno',
                'Description' => 'Tick to enable sandbox mode',
            ),
            // The test secret key
            'sandboxSecretKey' => array(
                'FriendlyName' => 'Sandbox Secret Key',
                'Type' => 'password',
                'Default' => '',
                'Description' => 'Enter your sandbox secret key here',
            ),
        );
    }
}

if (!function_exists('fedapay_link')) {
    /**
     * Payment link.
     *
     * Required by third party payment gateway modules only.
     *
     * Defines the HTML output displayed on an invoice. Typically consists of an
     * HTML form that will take the user to the payment gateway endpoint.
     *
     * @param array $params Payment Gateway Module Parameters
     *
     * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
     *
     * @return string
     */
    function fedapay_link($params)
    {
        // Invoice Parameters
        $invoiceId = $params['invoiceid'];
        $description = $params["description"];
        $amount = $params['amount'];
        $currency = $params['currency'];
        // Client Parameters
        $firstname = $params['clientdetails']['firstname'];
        $lastname = $params['clientdetails']['lastname'];
        $email = $params['clientdetails']['email'];
        $country = $params['clientdetails']['country'];
        $phone = $params['clientdetails']['phonenumber'];

        // System Parameters
        $companyName = $params['companyname'];
        $systemUrl = $params['systemurl'];
        $returnUrl = $params['returnurl'];
        $langPayNow = $params['langpaynow'];
        $moduleName = $params['paymentmethod'];

        $postfields = array();
        $postfields['invoice_id'] = $invoiceId;
        $postfields['description'] = $description;
        $postfields['amount'] = $amount;
        $postfields['currency'] = $currency;
        $postfields['firstname'] = $firstname;
        $postfields['lastname'] = $lastname;
        $postfields['email'] = $email;
        $postfields['country'] = $country;
        $postfields['phone'] = $phone;
        $postfields['return_url'] = $returnUrl;
        $postfields['system_url'] = $systemUrl;
        $postfields['payment_method'] = $moduleName;
        $postfields['token'] = generateCSRFToken('form');

        $postfields = encodeParams($postfields);

        $url = $systemUrl . '/modules/gateways/fedapay-php/process.php';

        $htmlOutput = '<form method="post" action="' . $url . '">';

        foreach ($postfields as $k => $v) {
            $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
        }

        $htmlOutput .= '<input class="btn btn-sm btn-success" type="submit" value="' . $langPayNow . '" />';
        $htmlOutput .= '</form>';

        return $htmlOutput;
    }
}
