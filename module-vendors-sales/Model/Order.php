<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order as BaseOrder;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * @method \Magento\Customer\Model\Customer getCustomer();
 * @method string getFirstname();
 * @method string getLastname();
 * @method string getMiddlename();
 * @method string getEmail();
 */
class Order extends \Magento\Framework\Model\AbstractModel
{

    const ENTITY = 'vendor_order';

    /**
     * Vendor Group Object
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * Vendor Group Object
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $_vendor;

    /**
     * @var TrackCollection
     */
    protected $_tracks;

    /**
     * Invoice collection
     * @var \Vnecoms\VendorsSales\Model\ResourceModel\Order\Invoice\Collection
     */
    protected $_invoiceCollection;

    /**
     * Creditmemo collection
     * @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection
     */

    protected $_creditCollection;

    /**
     * Shipment collection
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */

    protected $_shipmentCollection;

    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vendor_order';

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'vendor_order';

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory
     */
    protected $_historyCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory
     */
    protected $_trackCollectionFactory;

    /**
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory
     */
    protected $_orderHistoryFactory;

    /**
     * Order constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $_historyCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $_trackCollectionFactory
     * @param BaseOrder\Status\HistoryFactory $_orderHistoryFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $_historyCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $_trackCollectionFactory,
        \Magento\Sales\Model\Order\Status\HistoryFactory $_orderHistoryFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_historyCollectionFactory = $_historyCollectionFactory;
        $this->_trackCollectionFactory = $_trackCollectionFactory;
        $this->_orderHistoryFactory = $_orderHistoryFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }


    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Vnecoms\VendorsSales\Model\ResourceModel\Order');
    }

    /**
     * @param BaseOrder $order
     * @return $this
     */
    public function setOrder(\Magento\Sales\Model\Order $order){
        $this->_order = $order;
        return $this;
    }

    /**
     * Get order object
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_order = $om->create('Magento\Sales\Model\Order');
            $this->_order->load($this->getOrderId());
        }
        return $this->_order;
    }

    /**
     * Get vendor object
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if (!$this->_vendor) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_vendor = $om->create('Vnecoms\Vendors\Model\Vendor');
            $this->_vendor->load($this->getVendorId());
        }
        return $this->_vendor;
    }

    /**
     * Get order state
     * @return string
     */
    public function getState()
    {
        return $this->getData('state');
    }

    /**
     * Set order state
     * @return string
     */
    public function setState($state)
    {
        return $this->setData('state', $state);
    }


    /**
     * @return \Magento\Sales\Model\Order\Item[]
     */
    public function getAllItems()
    {
        if ($this->getData('all_items') == null) {
            $items = [];
            foreach ($this->getOrder()->getAllItems() as $item) {
                if ($item->getVendorOrderId() == $this->getId()) {
                    $items[$item->getId()] = $item;
                } elseif(($item->getVendorId() == $this->getVendorId()) && !$item->getVendorOrderId()){
                    $item->setVendorOrderId($this->getId())->save();
                    $items[$item->getId()] = $item;
                }
            }
            $this->setData('all_items', $items);
        }
        return $this->getData('all_items');
    }

    /**
     * @return array
     */
    public function getAllVisibleItems()
    {
        $items = [];
        foreach ($this->getAllItems() as $item) {
            if (!$item->getParentItemId()) {
                $items[$item->getId()] = $item;
            }
        }
        return $items;
    }

    /**
     * Check whether order is canceled
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->getState() === BaseOrder::STATE_CANCELED;
    }

    /**
     * Retrieve order unhold availability
     *
     * @return bool
     */
    public function canUnhold()
    {
        if ($this->getOrder()->isPaymentReview()) {
            return false;
        }
        return $this->getState() === BaseOrder::STATE_HOLDED;
    }

    /**
     * Retrieve order cancel availability
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function canCancel()
    {
        if ($this->canUnhold()) {
            return false;
        }
        if (!$this->getOrder()->canReviewPayment() && $this->getOrder()->canFetchPaymentReviewUpdate()) {
            return false;
        }

        $allInvoiced = true;
        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToInvoice()) {
                $allInvoiced = false;
                break;
            }
        }
        if ($allInvoiced) {
            return false;
        }

        $state = $this->getState();
        if ($this->isCanceled() || $state === BaseOrder::STATE_COMPLETE || $state === BaseOrder::STATE_CLOSED) {
            return false;
        }

        if ($this->getActionFlag(BaseOrder::ACTION_FLAG_CANCEL) === false) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve order invoice availability
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function canInvoice()
    {
        $order = $this->getOrder();
        if ($this->canUnhold() || $order->isPaymentReview()) {
            return false;
        }
        $state = $this->getState();
        if ($this->isCanceled() || $state === BaseOrder::STATE_COMPLETE || $state === BaseOrder::STATE_CLOSED) {
            return false;
        }

        if ($order->getActionFlag(BaseOrder::ACTION_FLAG_INVOICE) === false) {
            return false;
        }

        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToInvoice() > 0 && !$item->getLockedDoInvoice()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve order shipment availability
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function canShip()
    {
        $order = $this->getOrder();
        if ($this->canUnhold() || $order->isPaymentReview()) {
            return false;
        }

        if ($order->getIsVirtual() || $this->isCanceled()) {
            return false;
        }

        if ($order->getActionFlag(BaseOrder::ACTION_FLAG_SHIP) === false) {
            return false;
        }

        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToShip() > 0 && !$item->getIsVirtual() &&
                !$item->getLockedDoShip() && !$this->isRefunded($item)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Check if item is refunded.
     *
     * @param OrderItemInterface $item
     * @return bool
     */
    private function isRefunded(OrderItemInterface $item)
    {
        return $item->getQtyRefunded() == $item->getQtyOrdered();
    }

    /**
     * Retrieve order credit memo (refund) availability
     *
     * @return bool
     */
    public function canCreditmemo()
    {
        $order = $this->getOrder();
        if ($this->hasForcedCanCreditmemo()) {
            return $this->getForcedCanCreditmemo();
        }

        if ($this->canUnhold() || $order->isPaymentReview() ||
            $this->isCanceled() || $this->getState() === BaseOrder::STATE_CLOSED) {
            return false;
        }

        /**
         * We can have problem with float in php (on some server $a=762.73;$b=762.73; $a-$b!=0)
         * for this we have additional diapason for 0
         * TotalPaid - contains amount, that were not rounded.
         */
        $totalRefunded = $this->priceCurrency->round($this->getTotalPaid()) - $this->getTotalRefunded();
        if (abs($this->getGrandTotal()) < .0001) {
            return $this->canCreditmemoForZeroTotal($totalRefunded);
        }

        return $this->canCreditmemoForZeroTotalRefunded($totalRefunded);
    }

    /**
     * Retrieve credit memo for zero total refunded availability.
     *
     * @param float $totalRefunded
     * @return bool
     */
    private function canCreditmemoForZeroTotalRefunded($totalRefunded)
    {
        $isRefundZero = abs($totalRefunded) < .0001;
        // Case when Adjustment Fee (adjustment_negative) has been used for first creditmemo
        $hasAdjustmentFee = abs($totalRefunded - $this->getAdjustmentNegative()) < .0001;
        $hasActionFlag = $this->getOrder()->getActionFlag(BaseOrder::ACTION_FLAG_EDIT) === false;
        if ($isRefundZero || $hasAdjustmentFee || $hasActionFlag) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve credit memo for zero total availability.
     *
     * @param float $totalRefunded
     * @return bool
     */
    private function canCreditmemoForZeroTotal($totalRefunded)
    {
        $order = $this->getOrder();
        $totalPaid = $this->getTotalPaid();
        //check if total paid is less than grandtotal
        $checkAmtTotalPaid = $totalPaid <= $this->getGrandTotal();
        //case when amount is due for invoice
        $hasDueAmount = $this->canInvoice() && ($checkAmtTotalPaid);
        //case when paid amount is refunded and order has creditmemo created
        $creditmemos = ($order->getCreditmemosCollection() === false) ?
            true : (count($order->getCreditmemosCollection()) > 0);
        $paidAmtIsRefunded = $this->getTotalRefunded() == $totalPaid && $creditmemos;
        if (($hasDueAmount || $paidAmtIsRefunded) ||
            (!$checkAmtTotalPaid &&
                abs($totalRefunded - $this->getAdjustmentNegative()) < .0001)) {
            return false;
        }
        return true;
    }

    /**
     * Get invoice collection
     * @return \Vnecoms\VendorsSales\Model\ResourceModel\Order\Invoice\Collection
     */
    public function getInvoiceCollection()
    {
        if (!$this->_invoiceCollection) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_invoiceCollection = $om->create('Vnecoms\VendorsSales\Model\Order\Invoice')->getCollection();
            $this->_invoiceCollection->addFieldToFilter('vendor_order_id', $this->getId());
        }
        return $this->_invoiceCollection;
    }


    /**
     * Get credimemo collection
     * @return  \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection
     */
    public function getCreditmemoCollection()
    {
        if (!$this->_creditCollection) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_creditCollection = $om->create('Magento\Sales\Model\Order\Creditmemo')->getCollection();
            $this->_creditCollection->addFieldToFilter('vendor_order_id', $this->getId());
        }
        return $this->_creditCollection;
    }



    /**
     * Retrieve order shipments collection
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection|false
     */
    public function getShipmentsCollection()
    {
        if (!$this->_shipmentCollection) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_shipmentCollection = $om->create('Magento\Sales\Model\Order\Shipment')->getCollection();
            $this->_shipmentCollection->addFieldToFilter('vendor_order_id', $this->getId());
        }
        return $this->_shipmentCollection;
    }


    /**
     * Cancel order
     *
     * @return $this
     */
    public function cancel()
    {
        if ($this->canCancel()) {
            $this->registerCancellation();

            $this->_eventManager->dispatch('vendor_order_cancel_after', ['order' => $this]);
        }

        return $this;
    }

    /**
     * @param string $comment
     * @param bool $graceful
     * @param bool $backQty
     * @return $this
     */
    public function registerCancellation($comment = '', $graceful = true, $backQty = true)
    {
        if ($this->canCancel() || $this->getOrder()->isPaymentReview() || $this->getOrder()->isFraudDetected()) {
            $state = BaseOrder::STATE_CANCELED;
            if ($backQty) {
                foreach ($this->getAllItems() as $item) {
                    if ($state != BaseOrder::STATE_PROCESSING && $item->getQtyToRefund()) {
                        if ($item->getQtyToShip() > $item->getQtyToCancel()) {
                            $state = BaseOrder::STATE_PROCESSING;
                        } else {
                            $state = BaseOrder::STATE_COMPLETE;
                        }
                    }
                    $item->cancel();
                }
            }
            $order = $this->getOrder();
            $this->setSubtotalCanceled($this->getSubtotal() - $this->getSubtotalInvoiced());
            $this->setBaseSubtotalCanceled($this->getBaseSubtotal() - $this->getBaseSubtotalInvoiced());

            $this->setTaxCanceled($this->getTaxAmount() - $this->getTaxInvoiced());
            $this->setBaseTaxCanceled($this->getBaseTaxAmount() - $this->getBaseTaxInvoiced());

            $this->setShippingCanceled($this->getShippingAmount() - $this->getShippingInvoiced());
            $this->setBaseShippingCanceled($this->getBaseShippingAmount() - $this->getBaseShippingInvoiced());

            $this->setDiscountCanceled(abs($this->getDiscountAmount()) - $this->getDiscountInvoiced());
            $this->setBaseDiscountCanceled(abs($this->getBaseDiscountAmount()) - $this->getBaseDiscountInvoiced());

            $this->setTotalCanceled($this->getGrandTotal() - $this->getTotalPaid());
            $this->setBaseTotalCanceled($this->getBaseGrandTotal() - $this->getBaseTotalPaid());

            $this->setState($state)
                ->setStatus($order->getConfig()->getStateDefaultStatus($state))->save();

        } elseif (!$graceful) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot cancel this order.'));
        }
        return $this;
    }

    /**
     * Retrieve shipping method
     *
     * @param bool $asObject return carrier code and shipping method data as object
     * @return string|\Magento\Framework\DataObject
     */
    public function getShippingMethod($asObject = false)
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $module = $object_manager->get('Magento\Framework\Module\Manager');

        if ($module->isEnabled("Vnecoms_VendorsShipping")) {
            $shippingMethod = $this->getData("shipping_method");
            if ($shippingMethod)  {
                $shippingMethod = explode(\Vnecoms\VendorsShipping\Plugin\Shipping::SEPARATOR, $shippingMethod);
                $shippingMethod = $shippingMethod[0];
            } else {
                return new \Magento\Framework\DataObject(['carrier_code' => "", 'method' => ""]);
            }
        } else {
            return new \Magento\Framework\DataObject(['carrier_code' => "", 'method' => ""]);
        }

        if (!$asObject) {
            return $shippingMethod;
        } else {
            if ($shippingMethod) {
                list($carrierCode, $method) = explode('_', $shippingMethod, 2);
                return new \Magento\Framework\DataObject(['carrier_code' => $carrierCode, 'method' => $method]);
            } else {
                return new \Magento\Framework\DataObject(['carrier_code' => "", 'method' => ""]);
            }
        }
    }

    /**
     * Retrieve text formatted price value including order rate
     *
     * @param   float $price
     * @return  string
     */
    public function formatPriceTxt($price){
        return $this->getOrder()->formatPriceTxt($price);
    }

    /**
     * @return \Magento\Store\Model\Store
     */
    public function getStore() {
        return $this->getOrder()->getStore();
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderAddressInterface|null
     */
    public function getBillingAddress() {
        return $this->getOrder()->getBillingAddress();
    }

    /**
     * @return BaseOrder\Address|null
     */
    public function getShippingAddress() {
        return $this->getOrder()->getShippingAddress();
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface|mixed|null
     */
    public function getPayment() {
        return $this->getOrder()->getPayment();
    }

    /**
     * @return bool
     */
    public function isCurrencyDifferent() {
        return $this->getOrder()->isCurrencyDifferent();
    }

    /**
     * @return bool
     */
    public function formatPricePrecision($price, $precision, $addBrackets = false) {
        return $this->getOrder()->formatPricePrecision($price, $precision, $addBrackets = false);
    }

    /**
     * @return bool
     */
    public function canComment()
    {
        return $this->getOrder()->canComment();
    }

    /**
     * @return bool
     */
    public function getConfig()
    {
        return $this->getOrder()->getConfig();
    }

    /**
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->getOrder()->getCustomerEmail();
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->getOrder()->getCustomerName();
    }

    /**
     * @return int|null
     */
    public function getCustomerGroupId()
    {
        return $this->getOrder()->getCustomerGroupId();
    }

    /**
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->getOrder()->getStoreId();
    }

    /**
     * @return int|null
     */
    public function getStoreName()
    {
        return $this->getOrder()->getStoreName();
    }

    /**
     * Retrieve label of order status
     *
     * @return string
     * @throws LocalizedException
     */
    public function getStatusLabel()
    {
        return $this->getConfig()->getStatusLabel($this->getStatus());
    }

    /**
     * @return string
     */
    public function getRealOrderId()
    {
        return $this->getOrder()->getRealOrderId();
    }

    /**
     * @return string
     */
    public function getIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * Return collection of order status history items.
     *
     * @return HistoryCollection
     */
    public function getStatusHistoryCollection()
    {
        $collection = $this->_historyCollectionFactory->create()
            ->addFieldToFilter('vendor_id', $this->getVendorId())
            ->addFieldToFilter('vendor_order_status', $this->getId())
            ->setOrder('created_at', 'desc')
            ->setOrder('entity_id', 'desc');
        if ($this->getId()) {
            foreach ($collection as $status) {
                $status->setOrder($this->getOrder());
            }
        }
        return $collection;
    }

    /**
     * Retrieve order tracking numbers collection
     *
     * @return TrackCollection
     */
    public function getTracksCollection()
    {
        if (empty($this->_tracks)) {
            $shipmentIds = $this->getShipmentsCollection()->getAllIds();
            $this->_tracks = $this->_trackCollectionFactory->create()->setOrderFilter($this->getOrder())
                ->addFieldToFilter('parent_id',['in' => $shipmentIds]);

            if ($this->getId()) {
                $this->_tracks->load();
            }
        }
        return $this->_tracks;
    }

    /**
     * Add a comment to order.
     *
     * Different or default status may be specified.
     *
     * @param string $comment
     * @param bool|string $status
     * @return OrderStatusHistoryInterface
     * @deprecated 101.0.5
     * @see addCommentToStatusHistory
     */
    public function addStatusHistoryComment($comment, $status = false)
    {
        return $this->addCommentToStatusHistory($comment, $status, false);
    }

    /**
     * Add a comment to order status history.
     *
     * Different or default status may be specified.
     *
     * @param string $comment
     * @param bool|string $status
     * @param bool $isVisibleOnFront
     * @return OrderStatusHistoryInterface
     * @since 101.0.5
     */
    public function addCommentToStatusHistory($comment, $status = false, $isVisibleOnFront = false)
    {
        if (false === $status) {
            $status = $this->getStatus();
        } elseif (true === $status) {
            $status = $this->getConfig()->getStateDefaultStatus($this->getState());
        } else {
            $this->setStatus($status);
        }
        $history = $this->_orderHistoryFactory->create()->setStatus(
            $status
        )->setComment(
            $comment
        )->setEntityName(
            $this->getOrder()->getEntityType()
        )->setIsVisibleOnFront(
            $isVisibleOnFront
        );
        $this->getOrder()->addStatusHistory($history);
        return $history;
    }
    
    /**
     * Return is_virtual
     *
     * @return int|null
     */
    public function getIsVirtual()
    {
        return $this->getOrder()->getIsVirtual();
    }

    /**
     * @return mixed|string
     */
    public function getDiscountDescription() {
        $parentOrderDiscountDescription = $this->getOrder()->getData('discount_description');
        if ($parentOrderDiscountDescription) {
            $result = $parentOrderDiscountDescription.", ".$this->getData("discount_description");
            $result = trim($result);
            $result = trim($result, ",");
            $result = trim($result);
            return $result;
        }
        return $this->getData("discount_description");
    }

}
