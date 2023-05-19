<?php

namespace Myzx\PhpHelper\Http;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Client
 * @method Response get($url, $data = null, $options = [])
 * @method Response post($url, $data = null, $options = [])
 * @method Response postJSON($url, $data = null, $options = [])
 * @method Response put($url, $data = null, $options = [])
 * @method Response delete($url, $data = null, $options = [])
 * @method Response upload($url, $data = null, $options = [])
 */
class Client
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client = null;

    /**
     * @var static
     */
    protected static $instance = null;

    /**
     * Client constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->client = new \GuzzleHttp\Client($options);
    }

    /**
     * @throws GuzzleException
     */
    public function request($method, $url, $data = null, $options = []): Response
    {
        $method = strtoupper($method);
        if ($data) {
            if ('POST' == $method) {
                $options['form_params'] = $data;
            } elseif ('POSTJSON' == $method) {
                $method = 'POST';
                if (!isset($options['headers'])) {
                    $options['headers'] = [];
                }
                $options['headers']['Content-Type'] = 'application/json';
                $options['body'] = json_encode($data);
                $data = null;
            } elseif ('PUT' == $method) {
                $options['form_params'] = $data;
            } elseif ('UPLOAD' == $method) {
                $options['multipart'] = $data;
            } elseif ('DELETE' == $method) {
                $options['query'] = $data;
            } else {
                $options['query'] = $data;
            }
        }
        try {
            $response = $this->client->request($method, $url, $options);
        } catch (BadResponseException $e) {
            return new Response($e->getResponse(), $e);
        }
        return new Response($response);
    }

    /**
     * 获取默认实例
     * @return Client|null
     */
    public static function instance(): ?Client
    {
        if (static::$instance===null) {
            self::$instance = new static([
                'timeout' => 30,
                'connect_timeout' => 5,
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => true,
                    'protocols' => ['http', 'https'],
                    'track_redirects' => false,
                ],
            ]);
        }
        return self::$instance;
    }

    /**
     * 动态方法
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        array_unshift($arguments, $name);
        return call_user_func_array([$this, 'request'], $arguments);
    }

    /**
     * 静态调用动态方法
     *
     * @param string $name
     * @param array $arguments
     * @return false|mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([static::instance(), $name], $arguments);
    }
}