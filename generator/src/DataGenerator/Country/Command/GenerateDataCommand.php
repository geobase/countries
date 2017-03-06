<?php

namespace Smart\Geo\Generator\DataGenerator\Country\Command;

use Smart\Geo\Generator\Command;
use Smart\Geo\Generator\DataGenerator\Country\CountryDataGenerator;
use Smart\Geo\Generator\DataGenerator\Country\CountryDataMapper;
use Smart\Geo\Generator\DataGenerator\Country\CountryDataWriter;
use Smart\Geo\Generator\Provider\Cache;
use Smart\Geo\Generator\Provider\OpenStreetMap\OpenStreetMapCache;
use Smart\Geo\Generator\Provider\Wikipedia\WikipediaCache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDataCommand extends Command
{
    protected function configure()
    {
        $this->setName('country:database:create')
            ->addOption('void-cache', 'vc')
            ->setDescription('Create the Country Database');
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

        if ($input->getOption('void-cache')) {
            (new Cache($this->getContainer()))->voidCache();
        }

        $output->write('Creating the Country Database: ');

        $countries = (new CountryDataGenerator($this->getContainer()))->genereteAllCountries();
        $countries = (new CountryDataMapper($this->getContainer()))->mapGeneratedCountryToArray($countries);

        (new CountryDataWriter($this->getContainer()))->writeCountryData($countries);

        $output->write('[ <fg=green>OK</fg=green> ]', true);
    }
}
