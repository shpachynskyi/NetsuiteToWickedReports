<?php

namespace Custom\NetSuiteToWickedReports\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends Command
{
    const NAME = 'custom:netsuite_to_wicked_reports:sync';

    protected $syncHelper;

    protected function configure()
    {
        $options = [
            new InputOption(
                self::NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Name'
            )
        ];
        $this->setName('custom_netsuite_to_wicked_reports_sync');
        $this->setDescription('Sync orders from netsuite to wickedreports');
        $this->setDefinition($options);

        parent::configure();
    }

    /**
     * Sync constructor.
     * @param \Custom\NetSuiteToWickedReports\Helper\Sync $syncHelper
     * @param string|null $name
     */
    public function __construct(
        \Custom\NetSuiteToWickedReports\Helper\Sync $syncHelper,
        string $name = null
    ) {
        $this->syncHelper = $syncHelper;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->syncHelper->execute();
    }
}
