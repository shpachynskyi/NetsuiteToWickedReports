<?php

namespace Custom\NetSuiteToWickedReports\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class LogLevel implements ArrayInterface
{
    const DISABLED = 0;
    const FULL = 1;
    const ONLY_ERROR = 2;

    const LIST_LOG_LEVEL = [
        self::DISABLED => 'Disabled',
        self::FULL => 'Full',
        self::ONLY_ERROR => 'Only error',
    ];

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return self::LIST_LOG_LEVEL;
    }
}
