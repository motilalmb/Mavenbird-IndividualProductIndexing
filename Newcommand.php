<?php

namespace Mavenbird\IndividualProductIndexing\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Catalog\Model\ProductFactory;

class Newcommand extends Command
{
    const SKU = 'sku';
    const ID = 'id';
    
    protected $indexerRegistry;
    protected $productFactory;

    public function __construct(
        IndexerRegistry $indexerRegistry,
        ProductFactory $productFactory
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->productFactory = $productFactory;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('indexer:reindex:Individual')
             ->setDescription('Reindex Individual Product.')
             ->addOption(
                self::SKU,
                null,
                InputOption::VALUE_REQUIRED,
                'Reindex Individual product by SKU'
             )
             ->addOption(
                self::ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Reindex Individual product by ID'
             );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $indexList = [
                'catalog_category_product',
                'catalog_product_category',
                'catalog_product_attribute',
                'cataloginventory_stock',
                'inventory',
                'catalogsearch_fulltext',
                'catalog_product_price',
            ];

            $skuOption = $input->getOption(self::SKU);
            $idOption = $input->getOption(self::ID);

            if ($skuOption || $idOption) {
                $productIds = $skuOption ? explode(',', $skuOption) : explode(',', $idOption);

                foreach ($indexList as $index) {
                    $indexer = $this->indexerRegistry->get($index);
                    
                    foreach ($productIds as $id) {
                        if ($skuOption) {
                            $id = $this->getProductIdBySku($id);
                        }
                        $indexer->reindexList([$id]);
                    }

                    $output->writeln('<info>' . $indexer->getTitle() . ' index has been rebuilt successfully</info>');
                }
                return Command::SUCCESS; 
            } else {
                $output->writeln('<error>Please add --id=1 or --sku=24-DN,24-DN</error>');
                $output->writeln('<comment>For Ex: php bin/magento indexer:reindex:Individual --sku=10-DN-12</comment>');
                return Command::INVALID; 
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE; 
        }
    }

    public function getProductIdBySku($sku)
    {
        $product = $this->productFactory->create();
        return $product->getIdBySku($sku);
    }
}
