<?php

namespace FedaPay;

use FedaPay\Util\Util;

/**
 * Class Transaction
 *
 * @property int $id
 * @property string $reference
 * @property string $description
 * @property string $callback_url
 * @property string $amount
 * @property string $status
 * @property int $transaction_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @package FedaPay
 */
class Transaction extends Resource
{
    /**
     * @param array|string $id The ID of the transaction to retrieve
     * @param array|null $headers
     *
     * @return Transaction
     */
    public static function retrieve($id, $headers = [])
    {
        return self::_retrieve($id, $headers);
    }

    /**
     * @param array|null $params
     * @param array|string|null $headers
     *
     * @return Collection of Transactions
     */
    public static function all($params = [], $headers = [])
    {
        return self::_all($params, $headers);
    }

    /**
     * @param array|null $params
     * @param array|string|null $headers
     *
     * @return Transaction The created transaction.
     */
    public static function create($params = [], $headers = [])
    {
        return self::_create($params, $headers);
    }

    /**
     * @param string $id The ID of the customer to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Transaction The updated transaction.
     */
    public static function update($id, $params = [], $headers = [])
    {
        return self::_update($id, $params, $headers);
    }

    /**
     * @param array|string|null $headers
     *
     * @return Transaction The saved transaction.
     */
    public function save($headers = [])
    {
        return $this->_save($headers);
    }

    /**
     * @param array $headers
     *
     * @return void
     */
    public function delete($headers = [])
    {
        return $this->_delete($headers);
    }

    /**
     * Generate a payment token and url
     * @return FedaPay\FedaPayObject
     */
    public function generateToken($params = [], $headers = [])
    {
        $url = $this->instanceUrl() . '/token';

        list($response, $opts) = static::_staticRequest('post', $url, $params, $headers);
        return Util::arrayToFedaPayObject($response, $opts);
    }
}
