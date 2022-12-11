<?php
/**
 * @category    Magento
 * @package     Magento_Sales
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Block\Adminhtml\Order;

use Magento\Backend\Block\Widget\Context;

class View extends \Magento\Backend\Block\Widget\Container
{

    /**
     * @var \Vnecoms\VendorsSales\Model\ResourceModel\Order\Collection
     */
    protected $vendorCollection;

    /**
     * Tax module helper
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManage;

    /**
     * View constructor.
     * @param Context $context
     * @param \Vnecoms\VendorsSales\Model\ResourceModel\Order\CollectionFactory $collection
     * @param \Magento\Framework\Module\Manager $moduleManage
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Vnecoms\VendorsSales\Model\ResourceModel\Order\CollectionFactory $collection,
        \Magento\Framework\Module\Manager $moduleManage,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->vendorCollection = $collection;
        $this->_moduleManage    = $moduleManage;
    }

    /**
     * Prepare button and grid
     */
    protected function _prepareLayout()
    {
        $order = $this->getParentBlock()->getOrder();
        $vendorOrderCollection =$this->vendorCollection->create()
            ->addFieldToFilter('order_id', $order->getId());
        if ($vendorOrderCollection->count()) {
            $isVendorShip  = $this->_moduleManage->isEnabled("Vnecoms_VendorsShipping");
            if ($isVendorShip) {
                $om = \Magento\Framework\App\ObjectManager::getInstance();
                $helper = $om->create('Vnecoms\VendorsShipping\Helper\Data');
                if ($helper->isEnabled()) {
                    $this->removeButton('order_creditmemo');
                    $this->removeButton('order_ship');
                }
            }
        }
        return parent::_prepareLayout();
    }
}
