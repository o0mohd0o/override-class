<?php

namespace Vnecoms\VendorsSales\Controller\Vendors\Order\Shipment;

use Magento\Framework\App\ObjectManager;

class NewAction extends \Vnecoms\Vendors\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsSales::sales_order_action_ship';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Vnecoms\VendorsSales\Controller\Vendors\Order\ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var \Magento\Shipping\Model\ShipmentProviderInterface
     */
    protected $shipmentProvider;

    /**
     * NewAction constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\VendorsSales\Controller\Vendors\Order\ShipmentLoader $shipmentLoader
     * @param \Magento\Shipping\Model\ShipmentProviderInterface|null $shipmentProvider
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Vnecoms\VendorsSales\Controller\Vendors\Order\ShipmentLoader $shipmentLoader,
        \Magento\Shipping\Model\ShipmentProviderInterface $shipmentProvider = null
    ) {
        $this->shipmentLoader = $shipmentLoader;
        $this->_coreRegistry = $context->getCoreRegsitry();
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
        $vendorOrder = $this->_objectManager->create('Vnecoms\VendorsSales\Model\Order')->load($this->getRequest()->getParam('order_id'));

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
            $this->_setActiveMenu('Vnecoms_VendorsSales::sales_shipments');
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shipments'));
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('New Shipment'));
            $this->_view->renderLayout();
        } else {
            $this->_redirect('*/order/view', ['order_id' => $this->getRequest()->getParam('order_id')]);
        }
    }
}
