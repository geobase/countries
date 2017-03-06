<?php

namespace Smart\Geo\Generator\DataGenerator\Region\Command;

use Smart\Geo\Generator\Command;
use Smart\Geo\Generator\DataGenerator\Region\RegionDataGenerator;
use Smart\Geo\Generator\DataGenerator\Region\RegionDataWriter;
use Smart\Geo\Generator\Provider\Cache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDataCommand extends Command
{
    protected function configure()
    {
        $this->setName('region:database:create')
            ->addOption('void-cache', 'vc')
            ->setDescription('Create the Region Database');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $output->write('Creating the Region Database: ');

        if ($input->getOption('void-cache')) {
            (new Cache($this->getContainer()))->voidCache();
        }

        $generator = new RegionDataGenerator($this->getContainer());
        $regions = $generator->genereteAllRegion();
        (new RegionDataWriter($this->getContainer()))->writeAllRegion($regions);

        $output->write('[ <fg=green>OK</fg=green> ]', true);
    }
}
