<?php

namespace App\Tests\Feature;

use App\Entity\Stock;
use App\Tests\DatabasePrimer;
use App\Http\FakeYahooFinanceApiClient;
use App\Tests\DatabaseDependantTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class RefreshStockProfileCommandTest extends DatabaseDependantTestCase
{
    /** @test */
    public function refresh_stock_profile_command_creates_new_records_correctly()
    {
        //setup
        $application = new Application(self::$kernel);

        //command
        $command = $application->find('app:refresh-stock-profile');
        $commandTester = new CommandTester($command);

        //set faked return content
        FakeYahooFinanceApiClient::$content = '{"symbol":"AMZN","shortName":"Amazon.com, Inc.","region":"US","exchangeName":"NasdaqGS","currency":"USD","price":88.975,"previousClose":88.45,"priceChange":0.52}';
        
        //do something
        $commandTester->execute([
            'symbol' => "AMZN",
            'region' => "US"
        ]);

        // make assertion
        //DB assertion
        $stockRepo = $this->em->getRepository(Stock::class);

        /** var Stock $stock */
        $stock = $stockRepo->findOneBy(['symbol' => "AMZN"]);

        $this->assertSame('USD', $stock->getCurrency());
        $this->assertSame('NasdaqGS', $stock->getExchangeName());
        $this->assertSame('AMZN', $stock->getSymbol());
        $this->assertSame('Amazon.com, Inc.', $stock->getShortName());
        $this->assertSame('US', $stock->getRegion());
        $this->assertGreaterThan(50, $stock->getPreviousClose());
        $this->assertGreaterThan(50, $stock->getPrice());
        $this->assertStringContainsString('Amazon.com, Inc. has been added/updated into DB.', $commandTester->getDisplay());
    }

    /** @test */
    public function refresh_stock_profile_command_updates_existing_records_correctly()
    {
        // setup
        //an existing stock record
        $stock = new Stock();
        $stock->setSymbol('AMZN');
        $stock->setRegion('US');
        $stock->setExchangeName('NasdaqGS');
        $stock->setCurrency('USD');
        $stock->setShortName('Amazon.com, Inc.');
        $stock->setPreviousClose(3000);
        $stock->setPrice(3100);
        $stock->setPriceChange(100);

        $this->em->persist($stock);
        $this->em->flush();

        $stockId = $stock->getId();
        $application = new Application(self::$kernel);

        //command
        $command = $application->find('app:refresh-stock-profile');
        $commandTester = new CommandTester($command);

        FakeYahooFinanceApiClient::$statusCode = 200;

        // Error content
        FakeYahooFinanceApiClient::setContent([
            'previous_close' => 88.45, 
            'price' => 88.975, 
            'price_change' => 0.52 
        ]);

        //do something
        //execute command
        $commandStatus = $commandTester->execute([
            'symbol' => "AMZN",
            'region' => "US"
        ]);

        //make assertions
        $stockRepo = $this->em->getRepository(Stock::class);
        $stockRecord = $stockRepo->find($stockId);

        $this->assertEquals(88.45, $stockRecord->getPreviousClose());
        $this->assertEquals(88.975, $stockRecord->getPrice());
        $this->assertEquals(0.52, $stockRecord->getPriceChange());

        $stockRecordCount = $stockRepo->createQueryBuilder('s')
        ->select('count(s.id)')
        ->getQuery()
        ->getSingleScalarResult();

        $this->assertEquals(0, $commandStatus);
        
        //check no duplicates, just 1 record not two or more
        $this->assertEquals(1, $stockRecordCount);

    }

    /** @test */
    public function non_200_status_code_responses_are_handled_correctly()
    {
        // setup
        $application = new Application(self::$kernel);

        //command
        $command = $application->find('app:refresh-stock-profile');
        $commandTester = new CommandTester($command);

        //non-200 response
        FakeYahooFinanceApiClient::$statusCode = 500;

        // Error content
        FakeYahooFinanceApiClient::$content = 'Finance API Client Error! ';

        // do something
        $commandStatus = $commandTester->execute([
            'symbol' => "AMZN",
            'region' => "US"
        ]);
        
        // make assertions
        //DB assertion
        $stockRepo = $this->em->getRepository(Stock::class);
        $stockRecordCount = $stockRepo->createQueryBuilder('s')
            ->select('count(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals(1, $commandStatus);
        $this->assertEquals(0, $stockRecordCount);
        $this->assertStringContainsString('Finance API Client Error!', $commandTester->getDisplay());
    }
}