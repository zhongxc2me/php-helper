<?php

namespace Myzx\PhpHelper\Http;

use ArrayAccess;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use LogicException;
use Myzx\PhpHelper\Support\Arr;
use Myzx\PhpHelper\Support\Traits\Macroable;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Response implements ArrayAccess
{
    use Macroable {
        Macroable::__call as macroCall;
    }

    /**
     * The underlying PSR response.
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * The decoded JSON response.
     *
     * @var array
     */
    protected $decoded;

    /**
     * @var BadResponseException
     */
    protected $exception;

    /**
     * Create a new response instance.
     *
     * @param MessageInterface $response
     * @param null $exception
     */
    public function __construct(MessageInterface $response, $exception = null)
    {
        $this->response = $response;
        $this->exception = $exception;
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function body(): string
    {
        return (string)$this->response->getBody();
    }

    /**
     * Get the JSON decoded body of the response as an array or scalar value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function json(string $key = null, $default = null)
    {
        if (!$this->decoded) {
            $this->decoded = json_decode($this->body(), true);
        }

        return $this->getData($key, $default);
    }

    /**
     * Get the XML decoded body of the response as an array or scalar value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return array
     */
    public function xml(string $key = null, $default = null)
    {
        if (!$this->decoded) {
            //将XML转为array,禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $this->decoded = json_decode(json_encode(simplexml_load_string($this->body(), 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }

        return $this->getData($key, $default);
    }

    /**
     * 获取数据
     * @param string|null $key
     * @param mixed $default
     * @return array|ArrayAccess|mixed
     */
    protected function getData(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->decoded;
        }

        return Arr::get($this->decoded, $key, $default);
    }

    /**
     * 是否为JSON响应
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->isContentType("application/json");
    }

    /**
     * 是否为XML响应
     *
     * @return bool
     */
    public function isXml(): bool
    {
        return $this->isContentType("application/xml");
    }

    /**
     * 响应解析为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        if ($this->isXml()) {
            return (array)$this->xml();
        }

        return (array)$this->json();
    }

    /**
     * Get the JSON decoded body of the response as an object.
     *
     * @return object
     */
    public function object(): object
    {
        if ($this->isXml()) {
            //将XML转为array,禁止引用外部xml实体
            libxml_disable_entity_loader(true);

            return json_decode(json_encode(simplexml_load_string($this->body(), 'SimpleXMLElement', LIBXML_NOCDATA)), false);
        } else {
            return json_decode($this->body(), false);
        }
    }

    /**
     * 获取相应内容类型
     *
     * @return bool
     */
    public function contentType(): bool
    {
        return $this->getHeaderLine('Content-Type');
    }

    /**
     * 响应是哪个类型
     *
     * @param string $contentType
     * @return bool
     */
    public function isContentType(string $contentType): bool
    {
        return stripos($this->contentType(), $contentType) !== false;
    }

    /**
     * Get a header from the response.
     *
     * @param string $header
     * @return string
     */
    public function header(string $header): string
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * Get the headers from the response.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function status(): int
    {
        return (int)$this->response->getStatusCode();
    }

    /**
     * Get the effective URI of the response.
     *
     * @return UriInterface|null
     */
    public function effectiveUri(): ?UriInterface
    {
        if ($this->transferStats) {
            return $this->transferStats->getEffectiveUri();
        }

        return null;
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool
     */
    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Determine if the response code was "OK".
     *
     * @return bool
     */
    public function ok(): bool
    {
        return $this->status() === 200;
    }

    /**
     * Determine if the response was a redirect.
     *
     * @return bool
     */
    public function redirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Determine if the response indicates a client or server error occurred.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Determine if the response indicates a client error occurred.
     *
     * @return bool
     */
    public function clientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Determine if the response indicates a server error occurred.
     *
     * @return bool
     */
    public function serverError(): bool
    {
        return $this->status() >= 500;
    }

    /**
     * Execute the given callback if there was a server or client error.
     *
     * @param callable $callback
     * @return $this
     */
    public function onError(callable $callback): Response
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Get the response cookies.
     *
     * @return CookieJar
     */
    public function cookies(): CookieJar
    {
        return $this->cookies;
    }

    /**
     * Get the handler stats of the response.
     *
     * @return array
     */
    public function handlerStats()
    {
        if ($this->transferStats) {
            return $this->transferStats->getHandlerStats() ?? [];
        }

        return [];
    }

    /**
     * Close the stream and any underlying resources.
     *
     * @return $this
     */
    public function close()
    {
        $this->response->getBody()->close();

        return $this;
    }

    /**
     * Get the underlying PSR response for the response.
     *
     * @return ResponseInterface
     */
    public function toPsrResponse()
    {
        return $this->response;
    }

    /**
     * Create an exception if a server or client error occurred.
     *
     * @return RequestException|void
     */
    public function toException()
    {
        if ($this->exception) {
            return $this->exception;
        }

        if ($this->failed()) {
            return new RequestException($this);
        }
    }

    /**
     * Throw an exception if a server or client error occurred.
     *
     * @return $this
     *
     */
    public function throw(): Response
    {
        $callback = func_get_args()[0] ?? null;

        if ($this->failed()) {
            throw tap($this->toException(), function ($exception) use ($callback) {
                if ($callback && is_callable($callback)) {
                    $callback($this, $exception);
                }
            });
        }

        return $this;
    }

    /**
     * Throw an exception if a server or client error occurred and the given condition evaluates to true.
     *
     * @param bool $condition
     * @return $this
     *
     * @throws RequestException
     */
    public function throwIf(bool $condition): Response
    {
        return $condition ? $this->throw() : $this;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param string $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->json()[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     *
     * @throws \LogicException
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param string $offset
     * @return void
     *
     * @throws \LogicException
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }

    /**
     * Dynamically proxy other methods to the underlying response.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return static::hasMacro($method)
            ? $this->macroCall($method, $parameters)
            : $this->response->{$method}(...$parameters);
    }
}