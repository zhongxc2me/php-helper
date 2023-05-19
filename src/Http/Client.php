<?php

namespace Myzx\PhpHelper\Http;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Client
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
}