<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class FakeYahooFinanceApiClient implements FinanceApiClientInterface
{
    public static $statusCode = 200;
    public static $content = '';
    
    public function fetchStockProfile(string $symbol, string $region): JsonResponse
    {
        // return [
        //     'statusCode' => self::$statusCode,
        //     'content' => self::$content
        // ];

        //better use OOP
        return new JsonResponse(self::$content, self::$statusCode, [], $json = true);//already json, don't decode it

        //mine...
        // return new JsonResponse(['content' => self::$content, 'statusCode' => self::$statusCode, [], $json = true]);//already json, don't decode it

    }

    public static function setContent(array $overrides): void 
    {
        self::$content = json_encode(array_merge([
            'symbol' => 'AMZN',
            'region' => 'US',
            'exchange_name' => 'NasdaqGS',
            'currency' => 'US',
            'short_name' => 'Amazon.com, Inc.'
        ], $overrides));
    }
}