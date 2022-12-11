<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Controller\Adminhtml\Sales\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\App\ObjectManager;

class NewAction extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::shipment';

    /**
     * @var \Vnecoms\VendorsSales\Controller\Adminhtml\Sales\Shipment\ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Shipping\Model\ShipmentProviderInterface
     */
    private $shipmentProvider;

    /**
     * NewAction constructor.
     * @param Action\Context $context
     * @param ShipmentLoader $shipmentLoader
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Shipping\Model\ShipmentProviderInterface|null $shipmentProvider
     */
    public function __construct(
        Action\Context $context,
        \Vnecoms\VendorsSales\Controller\Adminhtml\Sales\Shipment\ShipmentLoader $shipmentLoader,
        \Magento\Framework\Registry $registry,
        \Magento\Shipping\Model\ShipmentProviderInterface $shipmentProvider = null
    ) {
        $this->shipmentLoader = $shipmentLoader;
        $this->_coreRegistry = $registry;
        $this->shipmentProvider = $shipmentProvider ?: ObjectManager::getInstance()
            ->get(\Magento\Shipping\Model\ShipmentProviderInterface::class);
        parent::__construct($context);
    }


    /**
     * Shipment create page
     *
     * @return void
     */
    public function execute()
    {
        $vendorOrder = $this->_objectManager->create('Vnecoms\VendorsSales\Model\Order')->load($this->getRequest()->getParam('vorder_id'));

        $this->shipmentLoader->setOrderId($vendorOrder->getOrderId());
        $this->shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
        $this->shipmentLoader->setShipment($this->shipmentProvider->getShipmentData());
        $this->shipmentLoader->setTracking($this->getRequest()->getParam('tracking'));
        $this->shipmentLoader->setVendorOrder($vendorOrder);

        $shipment = $this->shipmentLoader->load();
        if ($shipment) {
            $comment = $this->_objectManager->get('Magento\Backend\Model\Session')->getCommentText(true);
            if ($comment) {
                $shipment->setCommentText($comment);
            }

            $this->_coreRegistry->register('vendor_order', $vendorOrder);

            $this->_view->loadLayout();
            $this->_setActiveMenu('Magento_Sales::sales_order');
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shipments'));
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('New Shipment (%1)', $vendorOrder->getVendor()->getVendorId()));
            $this->_view->renderLayout();
        } else {
            $this->_redirect('sales/order/view', ['order_id' => $vendorOrder->getOrder()->getId()]);
        }
    }
}
