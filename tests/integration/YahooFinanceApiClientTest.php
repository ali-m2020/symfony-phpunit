<?php

namespace App\Tests\integration;

use App\Tests\DatabaseDependantTestCase;

class YahooFinanceApiClientTest extends DatabaseDependantTestCase
{
    /**
     * @test
     * @group integration
     */
    public function yahoo_finance_api_client_returns_correct_data(): void
    {
        // setup
        // need YahooFinanceApiClient
        $yfac = self::$kernel->getContainer()->get('yahoo-finance-api-client');

        // do something
        $response = $yfac->fetchStockProfile('AMZN', 'US'); //symbol, region

        $stockProfile = json_decode($response->getContent());
        
        // make assertion
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('AMZN', $stockProfile->symbol);
        $this->assertSame('Amazon.com, Inc.', $stockProfile->shortName);
        $this->assertSame('US', $stockProfile->region);
        $this->assertSame('NasdaqGS', $stockProfile->exchangeName);
        $this->assertIsFloat($stockProfile->price);
        $this->assertIsFloat($stockProfile->previousClose);
        $this->assertIsFloat($stockProfile->priceChange);
    }

}