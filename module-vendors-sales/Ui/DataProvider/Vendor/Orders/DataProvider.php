<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Ui\DataProvider\Vendor\Orders;

use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Ui\Component\Listing\AttributeRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param AttributeRepository $attributeRepository
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        AttributeRepository $attributeRepository,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Magento\Framework\App\RequestFactory $requestFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->attributeRepository = $attributeRepository;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        if ($vendorSession->getVendor()->getId()) {
            $this->addFilter( $this->filterBuilder->setField("vendor_id")
                ->setValue($vendorSession->getVendor()->getId())->setConditionType('eq')->create());
        }
    }


    /**
     * @return void
     */
    protected function prepareUpdateUrl()
    {
        parent::prepareUpdateUrl();
        $orderId = $this->request->getParam("vorder_id");
        if ($orderId) {
            $this->data['config']['update_url'] = sprintf(
                '%s%s/%s/',
                $this->data['config']['update_url'],
                "vendor_order_id",
                $orderId
            );
            $this->addFilter(
                $this->filterBuilder->setField("vendor_order_id")->setValue($orderId)->setConditionType('eq')->create()
            );
        }
    }
}
