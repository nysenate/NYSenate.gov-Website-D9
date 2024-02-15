<?php

namespace DMore\ChromeDriver;

use Behat\Mink\Exception\DriverException;
use WebSocket\Client;
use WebSocket\ConnectionException;

abstract class DevToolsConnection
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var int
     */
    private $command_id = 1;

    /**
     * @var string
     */
    private $url;

    /**
     * @var int|null
     */
    private $socket_timeout;

    /**
     * @param $url
     * @param null $socket_timeout
     */
    public function __construct($url, $socket_timeout = null)
    {
        $this->url = $url;
        $this->socket_timeout = $socket_timeout;
    }

    /**
     * Check DevTools connection.
     *
     * @return bool
     */
    public function canDevToolsConnectionBeEstablished()
    {
        $url = $this->getUrl() . "/json/version";
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        $s = curl_exec($c);
        curl_close($c);

        return $s !== false && strpos($s, 'Chrome') !== false;
    }

    /**
     * Get the current URL.
     *
     * @return string
     */
    protected function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Connect to the client.
     *
     * @param null $url
     */
    public function connect($url = null): void
    {
        $url = $url == null ? $this->url : $url;
        $options = ['fragment_size' => 2000000]; // Chrome closes the connection if a message is sent in fragments
        if (is_numeric($this->socket_timeout) && $this->socket_timeout > 0) {
            $options['timeout'] = (int)$this->socket_timeout;
        }
        $this->client = new Client($url, $options);
    }

    /**
     * Close the client connection.
     */
    public function close(): void
    {
        $this->client->close();
    }

    /**
     * Send a command to the client.
     *
     * @param string $command
     * @param array $parameters
     * @return null|string|string[][]
     * @throws \Exception
     */
    public function send($command, array $parameters = []): array
    {
        $payload['id'] = $this->command_id++;
        $payload['method'] = $command;
        if (!empty($parameters)) {
            $payload['params'] = $parameters;
        }

        $this->client->send(json_encode($payload));

        $data = $this->waitFor(
            function ($data) use ($payload) {
                return array_key_exists('id', $data) && $data['id'] == $payload['id'];
            }
        );

        if (isset($data['result'])) {
            return $data['result'];
        }

        return ['result' => ['type' => 'undefined']];
    }

    /**
     * Wait on response from client.
     *
     * @param callable $is_ready
     * @return mixed|null
     * @throws ConnectionException
     * @throws DriverException
     * @throws StreamReadException
     */
    protected function waitFor(callable $is_ready)
    {
        $data = [];
        while (true) {
            try {
                $response = $this->client->receive();
            } catch (ConnectionException $exception) {
                $message = $exception->getMessage();
                if ($json = mb_substr($message, strpos($message, '{'))) {
                    if ($state = json_decode($json, true)) {
                        throw new StreamReadException($message, 101, $state, $exception);
                    }
                }
                throw $exception;
            }

            if (is_null($response)) {
                return null;
            }

            if ($data = json_decode($response, true)) {
                if (array_key_exists('error', $data)) {
                    $message = isset($data['error']['data']) ?
                        $data['error']['message'] . '. ' . $data['error']['data'] : $data['error']['message'];
                    throw new DriverException($message, $data['error']['code']);
                }

                // What's this doing?
                if ($this->processResponse($data)) {
                    break;
                }

                if ($is_ready($data)) {
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Process a client response.
     *
     * @param  array $data
     * @return bool
     * @throws DriverException
     */
    abstract protected function processResponse(array $data): bool;
}
