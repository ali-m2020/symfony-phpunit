<?php

namespace App\Tests;

use App\Entity\Stock;
use App\Tests\DatabaseDependantTestCase;

class StockTest extends DatabaseDependantTestCase
{
    
    /** @test */
    public function a_stock_record_can_be_created_in_db()
    {
        // setup
        // stock
        // dd($this->em);
        $stock = new Stock();
        $stock->setSymbol('AMZN');
        $stock->setShortName('Amazon Inc.');
        $stock->setCurrency('USD');
        $stock->setExchangeName('Nazdaq');
        $stock->setRegion('US');
        
        $price = 1000;
        $prevoiusClose = 1100;
        $priceChange = $price - $prevoiusClose;

        $stock->setPrice($price);
        $stock->setPreviousClose($prevoiusClose);
        $stock->setPriceChange($priceChange);

        $this->em->persist($stock);
        
        // do something
        $this->em->flush();

        //grab the just-created-record in temporary db in memory
        $stockRepo = $this->em->getRepository(Stock::class);
        $stockRecord = $stockRepo->findOneBy(['symbol' => "AMZN"]);
        
        // make assertion
        $this->assertEquals('Amazon Inc.', $stockRecord->getShortName());
        $this->assertEquals('USD', $stockRecord->getCurrency());
        $this->assertEquals('Nazdaq', $stockRecord->getExchangeName());
        $this->assertEquals('US', $stockRecord->getRegion());
        $this->assertEquals(1000, $stockRecord->getPrice());
        $this->assertEquals(1100, $stockRecord->getPreviousClose());
        $this->assertEquals(-100, $stockRecord->getPriceChange());
    }
}