<?php

namespace Custom\NetSuiteToWickedReports\Helper;

use Custom\NetSuiteToWickedReports\Model\Config\Source\LogLevel;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Zend\Log\Logger as ZendLogger;
use Zend\Log\Writer\Stream as WriterStream;

class Logger extends AbstractHelper
{
    /**
     * @var ZendLogger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var string file path for log
     */
    protected $filePath = '/var/log/netsuite_to_wickedreports.log';

    /**
     * Logger constructor.
     * @param Context $context
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
        $writer = new WriterStream(BP . $this->filePath);
        $this->logger = new ZendLogger();
        $this->logger->addWriter($writer);
        parent::__construct($context);
    }

    /**
     * @return ZendLogger
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param $message
     * @param array $extra
     */
    public function err($message, $extra = [])
    {
        if ($this->dataHelper->getLogLevel() != LogLevel::DISABLED) {
            $this->getLogger()->err($message, $extra);
        }
    }

    /**
     * @param $message
     * @param array $extra
     */
    public function warn($message, $extra = [])
    {
        if ($this->dataHelper->getLogLevel() == LogLevel::FULL) {
            $this->getLogger()->warn($message, $extra);
        }
    }
}
