<?php

namespace Custom\NetSuiteToWickedReports\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\IntegrationException;
use NetSuite\Classes\SearchEnumMultiSelectField;
use NetSuite\Classes\SearchMoreWithIdRequest;
use NetSuite\Classes\SearchRequest;
use NetSuite\Classes\TransactionSearchBasic;
use NetSuite\NetSuiteService;
use WickedReports\Api\LatestEndpoint\Response;
use WickedReports\Exception\ValidationException;
use WickedReports\WickedReports;
use WickedReports\WickedReportsException;

class Sync extends AbstractHelper
{

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var NetSuiteService
     */
    protected $service;

    /**
     * @var SearchEnumMultiSelectField
     */
    protected $searchEnumMultiSelectField;

    /**
     * @var responce id netsuite
     */
    protected $searchId;

    /**
     * @var WickedReports
     */
    protected $wickedReportsApi;

    /**
     * @var Logger
     */
    protected $customLogger;

    /**
     * Sync constructor.
     * @param Context $context
     * @param Data $dataHelper
     * @param Logger $customLogger
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        Logger $customLogger
    ) {
        $this->customLogger = $customLogger;
        $this->dataHelper = $dataHelper;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function execute()
    {
        try {
            if (!$this->dataHelper->allIsEnabledAndConfigured()) {
                return false;
            }
            $resultWicked = $this->getLastOrdersFromWickedReports();
            if (empty($resultWicked->getData()) && empty($resultWicked->getData()['offset'])) {
                return false;
            }
            $pushedItemsToWicked = $resultWicked->getData()['offset'];
            $this->getSearchId();
            $countInNetsuite = $this->dataHelper->getCountElementInNetsuite();

            $needPageIndex = round($pushedItemsToWicked / $this->dataHelper->getPageSize());
            $needPageIndex = $needPageIndex == 0 ? 1 : $needPageIndex;
            $skipOnFirstIteration = $pushedItemsToWicked - $needPageIndex * $this->dataHelper->getPageSize();
            $skip = (bool)($skipOnFirstIteration > 0);

            $resultForLog = [];
            while ($needPageIndex < $countInNetsuite) {
                $resultForLog[] = $this->pushToWickedReports(
                    $this->prepareToPushInWickedReports(
                        $this->getPageSalesOrder($needPageIndex),
                        $skip ? $skipOnFirstIteration : 0
                    )
                );
                $skip = false;
                $needPageIndex++;
            }
            $this->logger($resultForLog);
            return true;
        } catch (\Exception $exception) {
            $this->customLogger->err($exception->getMessage());
            return false;
        }
    }

    /**
     * @param array $resultLog
     */
    protected function logger($resultLog = [])
    {
        foreach ($resultLog as $item) {
            $item = (array)$item;
            foreach ($item['errors'] as $itemSub) {
                $itemSub = (array)$itemSub;
                $error = $itemSub['error'];
                if (!empty($itemSub["item"]) && !empty($itemSub["item"]->SourceID)) {
                    $error .= '; Source id - ' . $itemSub["item"]->SourceID;
                }
                $this->customLogger->err($error);
            }
            if ($item['records'] != $item['totalRecords']) {
                $this->customLogger->warn('On one iteration something maybe went wrong. Records - ' . $item['records'] . ', totalRecords - ' . $item['totalRecords']);
            }
        }
    }

    /**
     * @param null $pageIndex
     * @return mixed
     * @throws IntegrationException
     * @throws ValidationException
     * @throws WickedReportsException
     */
    protected function getPageSalesOrder($pageIndex = null)
    {
        $SearchEnumMultiSelectField = $this->getSearchEnum();

        $search = new TransactionSearchBasic();
        $search->type = $SearchEnumMultiSelectField;

        $searchMoreRequest = new SearchMoreWithIdRequest();
        $searchMoreRequest->pageIndex = is_numeric($pageIndex) ? $pageIndex : (int)$this->getCountPushedOrders() / $this->dataHelper->getPageSize();
        $searchMoreRequest->searchId = $this->getSearchId();

        $searchResponse = $this->getService()->searchMoreWithId($searchMoreRequest);
        if ($searchResponse->searchResult->status->isSuccess && is_array($searchResponse->searchResult->recordList->record)) {
            return $searchResponse->searchResult->recordList->record;
        } else {
            throw new IntegrationException(__("Can`t get page " . $searchMoreRequest->pageIndex));
        }
    }
    /**
     * @return int
     * @throws ValidationException
     * @throws WickedReportsException
     */
    protected function getCountPushedOrders()
    {
        $data = $this->getLastOrdersFromWickedReports();
        if (!empty($data) && !empty($data->getData()) && !empty($data->getData()['offset'])) {
            return (int)$data->getData()['offset'];
        }
        $this->customLogger->err('something wrong with getting count pushed error to wicked reports. \Custom\NetSuiteToWickedReports\Helper\Sync::getCountPushedOrders');
        return 0;
    }

    /**
     * @param $data
     * @return bool|string
     * @throws ValidationException
     * @throws WickedReportsException
     */
    protected function pushToWickedReports($data)
    {
        return $this->getWickedReportsApi()->addOrders($data);
    }

    /**
     * @return Response
     * @throws ValidationException
     * @throws WickedReportsException
     */
    protected function getLastOrdersFromWickedReports()
    {
        return $this
            ->getWickedReportsApi()
            ->getOffset(
                $this->dataHelper->getSourceSystem(),
                'orders'
            );
    }

    /**
     * @return WickedReports
     */
    protected function getWickedReportsApi()
    {
        if (empty($this->wickedReportsApi)) {
            $this->wickedReportsApi = new WickedReports($this->dataHelper->getWickedReportsApiKey());
        }
        return $this->wickedReportsApi;
    }

    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    protected function prepareToPushInWickedReports($data, $skipCount = 0)
    {
        $result = [];

        $counter = 0;

        foreach ($data as $datum) {
            if ($counter++ < $skipCount) {
                continue;
            }

            $currentOrderId = $datum->tranId;
            $order = [];
            $order['SourceSystem'] = $this->dataHelper->getSourceSystem();
            $order['SourceID'] = $currentOrderId; /** maybe this variable $datum->tranId too can be id, or this $datum->otherRefNum, $datum->entity->internalId */
            $order['CreateDate'] = $this->getDateFromTimeStampString($datum->createdDate);
            $order['ContactID'] = $datum->entity->name;
            $order['ContactEmail'] = $datum->email;
            $order['OrderTotal'] = $datum->total;
            /**            $order['OrderCurrency'] = ''; /** in netsuite we have currency "US Dollar", but in wickedreports this variable have type varchar(3) */
            /**
             * //todo need to search order subscription, because we must send id subscription with order
             */
            $order['OrderItems'] = [];
            foreach ($datum->itemList->item as $item) {
                $orderItem = [];
                $orderItem['SourceSystem'] = $this->dataHelper->getSourceSystem();
                $orderItem['OrderID'] = $currentOrderId;
                $orderItem['OrderItemID'] = $item->lineUniqueKey;
                $orderItem['ProductID'] = $item->item->internalId;
                $orderItem['Qty'] = $item->quantity;
                $orderItem['PPU'] = $item->rate;
                $orderItem['SourceID'] = (string)$item->lineUniqueKey;
                $order['OrderItems'][] = $orderItem;
            }

            /**
             * @var $statusPayment - should be  allowed values "APPROVED","FAILED","REFUNDED" if empty will be considered as "APPROVED"
             */
            if ($datum->status == 'Billed') {
                $statusPayment = 'APPROVED';
            } else {
                $statusPayment = 'FAILED';
            }
            $order['OrderPayments'] = [
               [
                   'PaymentDate' => $this->getDateFromTimeStampString($datum->shipDate, true),
                   'Status' => $statusPayment,
                   'Amount' => $datum->total,
                   'OrderID' => $currentOrderId,
                   'SourceSystem' => $this->dataHelper->getSourceSystem()
               ]
            ];
            $result[] = $order;
        }

        return $result;
    }

    /**
     * @param $string
     * @param bool $stringFormat
     * @return string
     * @throws \Exception
     */
    protected function getDateFromTimeStampString($string, $stringFormat = false)
    {
        $date = new \DateTime();
        if (strtotime($string)) {
            $date->setTimestamp(strtotime($string));
        }
        if ($stringFormat) {
            return $date->format('Y-m-d h:i:s');
        }
        return $date;
    }

    /**
     * @return NetSuiteService
     */
    protected function getService()
    {
        if (empty($this->service)) {
            $this->service = new NetSuiteService($this->dataHelper->getConfigNetsuite());
            $this->service->setSearchPreferences(false, $this->dataHelper->getPageSize());
        }
        return $this->service;
    }

    /**
     * @return mixed
     * @throws IntegrationException
     * we need make first request and get first page, because we can`t get another page without searchId
     */
    protected function getSearchId()
    {
        if (empty($this->searchId)) {
            $search = new TransactionSearchBasic();
            $search->type = $this->getSearchEnum();
            $request = new SearchRequest();
            $request->searchRecord = $search;
            $searchResponse = $this->getService()->search($request);
            $this->dataHelper->setCountElementInNetsuite($searchResponse->searchResult->totalPages);
            if ($searchResponse->searchResult->status->isSuccess) {
                $this->searchId = $searchResponse->searchResult->searchId;
            } else {
                throw new IntegrationException("Can`t get search id");
            }
        }
        return $this->searchId;
    }

    /**
     * @return SearchEnumMultiSelectField
     */
    protected function getSearchEnum()
    {
        if (empty($this->searchEnumMultiSelectField)) {
            $this->searchEnumMultiSelectField = new SearchEnumMultiSelectField();
            $this->searchEnumMultiSelectField->searchValue = ['_salesOrder'];
            $this->searchEnumMultiSelectField->operator = 'anyOf';
        }
        return $this->searchEnumMultiSelectField;
    }
}
