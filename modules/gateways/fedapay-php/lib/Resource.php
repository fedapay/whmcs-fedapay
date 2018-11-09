<?php

namespace FedaPay;

use FedaPay\Util\Util;
use FedaPay\Util\Inflector;

/**
 * Class Resource
 *
 * @package FedaPay
 */
abstract class Resource extends FedaPayObject
{
    /**
     * @var FedaPay\Requestor
     */
    protected static $requestor;

    /**
     * Set requestor
     * @param FedaPay\Requestor $requestor
     */
    public static function setRequestor(Requestor $requestor)
    {
        self::$requestor = $requestor;
    }

    /**
     * Return the requestor
     * @return FedaPay\Requestor
     */
    public static function getRequestor()
    {
        return self::$requestor ?: new Requestor;
    }

    public static function className()
    {
        $class = get_called_class();
        // Useful for namespaces: Foo\Charge
        if ($postfixNamespaces = strrchr($class, '\\')) {
            $class = substr($postfixNamespaces, 1);
        }

        // Useful for underscored 'namespaces': Foo_Charge
        if ($postfixFakeNamespaces = strrchr($class, '')) {
            $class = $postfixFakeNamespaces;
        }

        if (substr($class, 0, strlen('FedaPay')) == 'FedaPay') {
            $class = substr($class, strlen('FedaPay'));
        }

        $class = str_replace('_', '', $class);
        $name = urlencode($class);
        $name = strtolower($name);

        return $name;
    }

    /**
     * @return string The endpoint URL for the given class.
     */
    public static function classPath()
    {
        $base = static::className();
        $plurial = Inflector::pluralize($base);

        return "/$plurial";
    }

    /**
     * @return string The instance endpoint URL for the given class.
     */
    public static function resourcePath($id)
    {
        if ($id === null) {
            $class = get_called_class();
            $message = 'Could not determine which URL to request: '
               . "$class instance has invalid ID: $id";
            throw new Error\InvalidRequest($message, null);
        }

        $base = static::classPath();
        $extn = urlencode($id);

        return "$base/$extn";
    }

    /**
     * @return string The full API URL for this API resource.
     */
    public function instanceUrl()
    {
        return static::resourcePath($this['id']);
    }

    /**
     * Validate request params
     * @param array $params
     * @throws Error\InvalidRequest
     */
    protected static function _validateParams($params = null)
    {
        if ($params && !is_array($params)) {
            $message = 'You must pass an array as the first argument to FedaPay API '
               . 'method calls.  (HINT: an example call to create a customer '
               . "would be: \"FedaPay\\Customer::create(array('firstname' => toto, "
               . "'lastname' => 'zoro', 'email' => 'admin@gmail.com', 'phone' => '66666666'))\")";
            throw new Error\InvalidRequest($message);
        }
    }

    /**
     * Static method to send request
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     *
     * @return array
     */
    protected static function _staticRequest($method, $url, $params = [], $headers = [])
    {
        $requestor = self::getRequestor();
        $response = $requestor->request($method, $url, $params, $headers);

        $options = [
            'apiVersion' => FedaPay::getApiVersion(),
            'environment' => FedaPay::getEnvironment()
        ];

        return [$response, $options];
    }

    /**
     * Static method to retrive a resource
     * @param mixed $id
     * @return FedaPay\FedaPayObject
     */
    protected static function _retrieve($id)
    {
        $url = static::resourcePath($id);
        $className = static::className();

        list($response, $opts) = static::_staticRequest('get', $url);
        $object = Util::arrayToFedaPayObject($response, $opts);

        return $object->$className;
    }

    /**
     * Static method to retrive a list of resources
     * @param array $params
     * @param array $headers
     *
     * @return array FedaPay\FedaPayObject
     */
    protected static function _all($params = [], $headers = [])
    {
        self::_validateParams($params);
        $path = static::classPath();
        list($response, $opts) = static::_staticRequest('get', $path, $params, $headers);

        return Util::arrayToFedaPayObject($response, $opts);
    }

    /**
     * Static method to create a resources
     * @param array $params
     * @param array $headers
     *
     * @return Resource
     */
    protected static function _create($params = [], $headers = [])
    {
        self::_validateParams($params);
        $url = static::classPath();
        $className = static::className();

        list($response, $opts) = static::_staticRequest('post', $url, $params, $headers);

        $object = Util::arrayToFedaPayObject($response, $opts);

        return $object->$className;
    }

    /**
     * Static method to update a resource
     * @param string $id     The ID of the API resource to update.
     * @param array $params The request params
     * @param array $headers the request headers
     *
     * @return Resource the updated API resource
     */
    protected static function _update($id, $params = [], $headers = [])
    {
        self::_validateParams($params);
        $url = static::resourcePath($id);
        $className = static::className();

        list($response, $opts) = static::_staticRequest('put', $url, $params, $headers);
        $object = Util::arrayToFedaPayObject($response, $opts);

        return $object->$className;
    }

    /**
     * Send a detele request
     * @param  array $params
     * @param  array $options
     */
    protected function _delete($headers = [])
    {
        $url = $this->instanceUrl();
        static::_staticRequest('delete', $url, [], $headers);

        return $this;
    }

    /**
     * Update the resource
     * @param array $headers the request headers
     *
     * @return Resource the updated API resource
     */
    protected function _save($headers = [])
    {
        $params = $this->serializeParameters();
        $className = static::className();
        $url = $this->instanceUrl();

        list($response, $opts) = static::_staticRequest('put', $url, $params, $headers);

        $klass = $opts['apiVersion'] . '/' . $className;

        $json = $response[$klass];
        $this->refreshFrom($json, $opts);

        return $this;
    }
}
