<?php

namespace App\Http;

use App\Http\FinanceApiClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YahooFinanceApiClient implements FinanceApiClientInterface
{
    private HttpClientInterface $httpClient;
    private const URL = 'https://apidojo-yahoo-finance-v1.p.rapidapi.com/stock/v2/get-profile';
    private const X_RAPID_API_HOST = 'apidojo-yahoo-finance-v1.p.rapidapi.com';
    private $rapidApiKey;

    public function __construct(HttpClientInterface $httpClient, $rapidApiKey)
    {
        $this->httpClient = $httpClient;
        $this->rapidApiKey = $rapidApiKey;
    }

    public function fetchStockProfile(string $symbol, string $region): JsonResponse
    {
        $response = $this->httpClient->request('GET', self::URL, [
            'query' => [
                'symbol' => $symbol,
                'region' => $region
            ],
            'headers' => [
                'x-rapidapi-host' => self::X_RAPID_API_HOST,
                'x-rapidapi-key' => $this->rapidApiKey
            ]
        ]);
        // dd($response->getContent());
        // dd($response->getStatusCode());
        
        if($response->getStatusCode() !== 200)
        {
            // dump($response->getStatusCode());
            // dd($response->getContent());
            return new JsonResponse('Finance API Client Error! . API returned status code: '.$response->getStatusCode().' with content: '.$response->getContent(), 400);
        }

        $stockProfile = json_decode($response->getContent())->price;
        // dd($stockProfile);

        $stockProfileAsArray = [
            'symbol' => $stockProfile->symbol,
            'shortName' => $stockProfile->shortName,
            'region' => $region,
            'exchangeName' => $stockProfile->exchangeName,
            'currency' => $stockProfile->currency,
            'price' => $stockProfile->regularMarketPrice->raw,
            'previousClose' => $stockProfile->regularMarketPreviousClose->raw,
            'priceChange' => $stockProfile->regularMarketPrice->raw - $stockProfile->regularMarketPreviousClose->raw,
        ];

        // return [
        //     'statusCode' => 200,
        //     'content' => json_encode($stockProfileAsArray)
        // ];

        //preferred way: use OOP instead of returning array
        return new JsonResponse($stockProfileAsArray, 200);
    }
}