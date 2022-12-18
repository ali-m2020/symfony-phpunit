<?php

namespace App\Tests;

use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseDependantTestCase extends KernelTestCase
{
    protected $em;
    
    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        DatabasePrimer::prime($kernel);

        //both works
        //gary's way
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        //databaseprimer's way
        // $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function tearDown(): void 
    {
        parent::tearDown();
        $this->em->close();
        $this->em = null;
    }
}