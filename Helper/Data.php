<?php

namespace Custom\NetSuiteToWickedReports\Helper;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * @var WriterInterface
     */
    protected $configWriter;

    protected $netsuiteEndpoint = 'module_netsuite_to_wicked_reports/netsuite/endpoint';
    protected $netsuiteHost = 'module_netsuite_to_wicked_reports/netsuite/host';
    protected $netsuiteAccount = 'module_netsuite_to_wicked_reports/netsuite/account';
    protected $netsuiteConsumerKey = 'module_netsuite_to_wicked_reports/netsuite/consumer_key';
    protected $netsuiteConsumerSecret = 'module_netsuite_to_wicked_reports/netsuite/consumer_secret';
    protected $netsuiteToken = 'module_netsuite_to_wicked_reports/netsuite/token';
    protected $netsuiteTokenSecret = 'module_netsuite_to_wicked_reports/netsuite/token_secret';
    protected $netsuiteSignatureAlgorithm = 'module_netsuite_to_wicked_reports/netsuite/signature_algorithm';
    protected $wickedReportsApiKey = 'module_netsuite_to_wicked_reports/wicked_reports/api_key';
    protected $sourceSystem = 'module_netsuite_to_wicked_reports/wicked_reports/source_system';
    protected $moduleEnable = 'module_netsuite_to_wicked_reports/general/enable';
    protected $logLevel = 'module_netsuite_to_wicked_reports/general/log_level';

    protected $pageSize = 20;

    protected $sourceSystemValue;

    protected $countElementInNetsuite = 0;

    /**
     * Data constructor.
     * @param Context $context
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Context $context,
        WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getNetsuiteEndpoint(): string
    {
        return $this->scopeConfig->getValue($this->netsuiteEndpoint);
    }

    /**
     * @return string
     */
    public function getNetsuiteHost(): string
    {
        return $this->scopeConfig->getValue($this->netsuiteHost);
    }

    /**
     * @return string
     */
    public function getNetsuiteAccount(): string
    {
        return $this->scopeConfig->getValue($this->netsuiteAccount);
    }

    /**
     * @return string
     */
    public function getNetsuiteConsumerKey(): string
    {
        return $this->scopeConfig->getValue($this->netsuiteConsumerKey);
    }

    /**
     * @return string
     */
    public function getNetsuiteConsumerSecret(): string
    {
        return $this->scopeConfig->getValue($this->netsuiteConsumerSecret);
    }

    /**
     * @return string
     */
    public function getNetsuiteToken(): string
    {
        return $this->scopeConfig->getValue($this->netsuiteToken);
    }

    /**
     * @return string
     */
    public function getNetsuiteTokenSecret(): string
    {
        return $this->scopeConfig->getValue($this->netsuiteTokenSecret);
    }

    /**
     * @return string
     */
    public function getNetsuiteSignatureAlgorithm(): string
    {
        return $this->scopeConfig->getValue($this->netsuiteSignatureAlgorithm);
    }

    /**
     * @return string
     */
    public function getWickedReportsApiKey(): string
    {
        return $this->scopeConfig->getValue($this->wickedReportsApiKey);
    }

    /**
     * @return string
     */
    public function getModuleEnable(): string
    {
        return $this->scopeConfig->getValue($this->moduleEnable);
    }

    /**
     * @return array
     */
    public function getConfigNetsuite()
    {
        return [
            "endpoint"       => $this->getNetsuiteEndpoint(),
            "host"           => $this->getNetsuiteHost(),
            "account"        => $this->getNetsuiteAccount(),
            "consumerKey"    => $this->getNetsuiteConsumerKey(),
            "consumerSecret" => $this->getNetsuiteConsumerSecret(),
            "token"          => $this->getNetsuiteToken(),
            "tokenSecret"    => $this->getNetsuiteTokenSecret(),
            "signatureAlgorithm" => $this->getNetsuiteSignatureAlgorithm(),
        ];
    }

    /**
     * @return bool
     */
    public function allIsEnabledAndConfigured()
    {
        $config = $this->getConfigNetsuite();
        $netsuiteConfigured = true;
        foreach ($config as $item) {
            if (empty($item)) {
                $netsuiteConfigured = false;
            }
        }
        return $this->isModuleOutputEnabled($this->_getModuleName())
                &&
                $this->getModuleEnable()
                &&
                !empty($this->getWickedReportsApiKey())
                &&
                !empty($this->getSourceSystem())
                &&
                $netsuiteConfigured;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return mixed
     */
    public function getSourceSystem()
    {
        if (empty($this->sourceSystemValue)) {
            $this->sourceSystemValue = $this->scopeConfig->getValue($this->sourceSystem);
        }
        return $this->sourceSystemValue;
    }


    public function getLogLevel()
    {
        return $this->scopeConfig->getValue($this->logLevel);
    }
    /**
     * @return int
     */
    public function getCountElementInNetsuite(): int
    {
        return $this->countElementInNetsuite;
    }

    /**
     * @param int $countElementInNetsuite
     */
    public function setCountElementInNetsuite(int $countElementInNetsuite): void
    {
        $this->countElementInNetsuite = $countElementInNetsuite;
    }
}
