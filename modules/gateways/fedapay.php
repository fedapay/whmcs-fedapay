<?php
/**
 * WHMCS Sample Payment Gateway Module
 *
 * Payment Gateway modules allow you to integrate payment solutions with the
 * WHMCS platform.
 *
 * This sample file demonstrates how a payment gateway module for WHMCS should
 * be structured and all supported functionality it can contain.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "gatewaymodule" and therefore all functions
 * begin "fedapay_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _config
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
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
        setup_fedapay_gateway($params);

        try {
            $url = create_fedapay_transaction($params);
        } catch (\Exception $e) {
            return display_fedapay_errors($e);
        }

        // System Parameters
        $langPayNow = $params['langpaynow'];

        $htmlOutput = '<form method="get" action="' . $url . '">';
        $htmlOutput .= '<input class="btn btn-success btn-sm" type="submit" value="' . $langPayNow . '" />';
        $htmlOutput .= '</form>';

        return $htmlOutput;
    }
}
