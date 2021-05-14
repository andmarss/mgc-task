<?php

namespace App\Api;

use GuzzleHttp\Client;
use Service\XmlGenerator;
use Service\XmlSettings;

class MGCApi
{
    const URI = 'https://test.mgc-loyalty.ru/';

    const LOGIN = 'openbroker';
    const PASSWORD = 'yw4Tb8vK';

    const URL_GET_CATEGORIES = '/v1/GetCategories/';
    const URL_GET_PRODUCTS = '/v1/GetProduct/';

    protected $hash = '';
    /**
     * @var Client
     */
    protected $client;

    protected static $transaction = 1;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => static::URI]);
    }

    public function getCategories(): array
    {
        static::$transaction = intval(session()->get('transaction', 1519));
        /**
         * @var string $xml
         */
        $xml = $this->getXml(__FUNCTION__);


        $response = $this->client->post(static::URL_GET_CATEGORIES, [
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
            ],
            'body' => $xml
        ]);

        if ($response->getStatusCode() !== 200) {
            var_dump($response->getBody()->getContents());
            return [];
        }
        /**
         * @var \SimpleXMLElement $response
         */
        $response = simplexml_load_string($response->getBody()->getContents());

        if (intval($response->status) === 1) {
            var_dump($response);
            return [];
        }

        $json = json_encode($response);
        /**
         * @var array $response
         */
        $response = json_decode($json, true);

        return isset($response['Categories']) && isset($response['Categories']['Category']) && is_array($response['Categories']['Category']) && $response['Categories']['Category']
            ? $response['Categories']['Category']
            : [];
    }

    /**
     * @param int $category_id
     * @return array
     */
    public function getProduct(int $category_id): array
    {
        static::$transaction = intval(session()->get('transaction', 1520));

        $attributes = [
            'categories' => [
                ['id' => $category_id]
            ],
            'limit' => [
                'offset' => 0,
                'count' => 1000
            ]
        ];

        /**
         * @var string $xml
         */
        $xml = $this->getXml(__FUNCTION__, $attributes);

        $response = $this->client->post(static::URL_GET_PRODUCTS, [
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
            ],
            'body' => $xml
        ]);

        if ($response->getStatusCode() !== 200) {
            return [];
        }
        /**
         * @var \SimpleXMLElement $response
         */
        $response = simplexml_load_string($response->getBody()->getContents());

        if (intval($response->status) === 1) {
            return [];
        }

        $json = json_encode($response);
        /**
         * @var array $response
         */
        $response = json_decode($json, true);

        return isset($response['Products']) && isset($response['Products']['Product']) && is_array($response['Products']['Product']) && $response['Products']['Product']
            ? $response['Products']['Product']
            : [];
    }

    protected function getXml(string $method, array $attributes = []): string
    {
        /**
         * @var XmlGenerator $generator
         */
        $generator = new XmlGenerator(
            (new XmlSettings())->useMemory()
        );

        $transaction = static::$transaction;

        session()->put('transaction', ++static::$transaction);

        // Получаем продукты
        if (isset($attributes['categories'])) {
            return $generator->generate(collect([
                'authentication' => [
                    'login' => static::LOGIN,
                    'transaction_id' => $transaction,
                    'method' => $method,
                    'hash' => md5($transaction . $method . static::LOGIN . static::PASSWORD)
                ],
                'parameters' => [
                    'categories' => $attributes['categories'],
                    'limit' => isset($attributes['limit']) ? $attributes['limit'] : []
                ]
            ]));
        } else { // Получаем категории
            return $generator->generate(collect([
                'authentication' => [
                    'login' => static::LOGIN,
                    'transaction_id' => $transaction,
                    'method' => $method,
                    'hash' => md5($transaction . $method . static::LOGIN . static::PASSWORD)
                ]
            ]));
        }
    }

    public function incrementTransactionId()
    {
        static::$transaction++;
    }
}