<?php

namespace App\Command;

use App\Entity\Stock;
use Psr\Log\LoggerInterface;
use App\Http\FinanceApiClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[AsCommand(
    name: 'app:refresh-stock-profile',
    description: 'Retrieve a stock profile from Yahoo Finance API. Update the record in the DB',
)]
class RefreshStockProfileCommand extends Command
{
    private EntityManagerInterface $em;
    private FinanceApiClientInterface $faci;
    private SerializerInterface $si;
    public function __construct(EntityManagerInterface $em, FinanceApiClientInterface $faci, SerializerInterface $si, LoggerInterface $li)
    {
        $this->em = $em;
        $this->faci = $faci;
        $this->si = $si;
        $this->li = $li;
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->addArgument('symbol', InputArgument::REQUIRED, 'Stock symbol, e.g. AMZN for Amazon')
            ->addArgument('region', InputArgument::REQUIRED, 'Region of the company, e.g. US for USA')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try{

            // 1. ping Yahoo API and grab the response
            // handle non-200 status
            // 2b. if record exists update, if not, create new record
            $stockProfile = $this->faci->fetchStockProfile($input->getArgument('symbol'), $input->getArgument('region'));
            
            if($stockProfile->getStatusCode() !== 200)
            {
                $output->writeln($stockProfile->getContent());
                return COMMAND::FAILURE;
            }


            //Attempt to find a record in DB using $stockProfile symbol
            $symbol = json_decode($stockProfile->getContent())->symbol ?? null;
            
            if($stock = $this->em->getRepository(Stock::class)->findOneBy(['symbol' => $symbol]))
            {
                // update if found
                $stock = $this->si->deserialize($stockProfile->getContent(), Stock::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $stock]);
            }
            else
            {
                // create a new record if not found
                /** @var Stock $stock */
                $stock = $this->si->deserialize($stockProfile->getContent(), Stock::class, 'json');
            }

            $this->em->persist($stock);
            $this->em->flush();

            //output feedback on terminal, even if it will be run as a cron job on prod
            $output->writeln($stock->getShortName() . ' has been added/updated into DB.');
            return Command::SUCCESS;
            
        } catch(\Exception $e){
            //log everything here and learn what went wrong
            $this->li->warning(
                get_class($e). ': '. $e->getMessage(). ' in '. $e->getFile(). ' on line '. $e->getLine(). ' using [symbol/region] ['. 
                $input->getArgument('symbol'). '/'. $input->getArgument('region'). ']'
            );
            return Command::FAILURE;
        }
    }
}
