<?php

namespace Custom\NetSuiteToWickedReports\Cron;

use Custom\NetSuiteToWickedReports\Helper\Sync as SyncHelper;

class Sync
{
    /**
     * @var SyncHelper
     */
    protected $syncHelper;

    /**
     * Sync constructor.
     * @param SyncHelper $syncHelper
     */
    public function __construct(
        SyncHelper $syncHelper
    ) {
        $this->syncHelper = $syncHelper;
    }

    public function execute()
    {
        $this->syncHelper->execute();
    }
}
