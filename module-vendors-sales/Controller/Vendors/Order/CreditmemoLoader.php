<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Controller\Vendors\Order;

use Magento\Framework\DataObject;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Vnecoms\VendorsSales\Model\Order\CreditmemoFactory;

/**
 * Class CreditmemoLoader
 *
 * @package Vnecoms\VendorsSales\Controller\Vendors\Order
 * @method CreditmemoLoader setCreditmemoId($id)
 * @method CreditmemoLoader setCreditmemo($creditMemo)
 * @method CreditmemoLoader setInvoiceId($id)
 * @method CreditmemoLoader setOrderId($id)
 * @method int getCreditmemoId()
 * @method string getCreditmemo()
 * @method int getInvoiceId()
 * @method int getOrderId()
 */
class CreditmemoLoader extends DataObject
{
    /**
     * @var CreditmemoRepositoryInterface;
     */
    protected $creditmemoRepository;

    /**
     * @var CreditmemoFactory;
     */
    protected $creditmemoFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_objectManager;
    /**
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @var \Vnecoms\VendorsSales\Model\Order\InvoiceFactory
     */
    protected $vendorInvoiceFactory;

    /**
     * CreditmemoLoader constructor.
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Backend\Model\Session $backendSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     * @param \Vnecoms\VendorsSales\Model\Order\InvoiceFactory $vendorInvoiceFactory
     * @param array $data
     */
    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        \Vnecoms\VendorsSales\Model\Order\InvoiceFactory $vendorInvoiceFactory,
        array $data = []
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->orderFactory = $orderFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->eventManager = $eventManager;
        $this->backendSession = $backendSession;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->stockConfiguration = $stockConfiguration;
        $this->_objectManager = $objectManagerInterface;
        $this->vendorInvoiceFactory = $vendorInvoiceFactory;
        parent::__construct($data);
    }

    /**
     * Get requested items qtys and return to stock flags
     *
     * @return array
     */
    protected function _getItemData()
    {
        $data = $this->getCreditmemo();
        if (!$data) {
            $data = $this->backendSession->getFormData(true);
        }

        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = [];
        }
        return $qtys;
    }

    /**
     * Check if creditmeno can be created for order
     * @param \Vnecoms\VendorsSales\Model\Order $order
     * @return bool
     */
    protected function _canCreditmemo($vendorOrder)
    {
        /**
         * Check order existing
         */
        if (!$vendorOrder->getId()) {
            $this->messageManager->addErrorMessage(__('The order no longer exists.'));
            return false;
        }

        /**
         * Check creditmemo create availability
         */
        if (!$vendorOrder->canCreditmemo()) {
            $this->messageManager->addErrorMessage(__('We can\'t create credit memo for the order.'));
            return false;
        }
        return true;
    }

    /**
     * @param \Vnecoms\VendorsSales\Model\Order $order
     * @return $this|bool
     */
    protected function _initVendorInvoice($order)
    {
        $invoiceId = $this->getInvoiceId();
        if ($invoiceId) {
            $invoice = $this->vendorInvoiceFactory->create()->load($invoiceId);
            $invoice->setVendorOrder($order);
            if ($invoice->getId()) {
                return $invoice;
            }
        }
        return false;
    }

    /**
     * Initialize creditmemo model instance
     *
     * @return \Magento\Sales\Model\Order\Creditmemo|false
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function load()
    {
        //  return null;
        $creditmemo = false;
        $creditmemoId = $this->getCreditmemoId();
        $orderId = $this->getOrderId();

        if ($creditmemoId) {
            $creditmemo = $this->creditmemoRepository->get($creditmemoId);

            $vendorOrder = $this->_objectManager->create('Vnecoms\VendorsSales\Model\Order')->load($creditmemo->getVendorOrderId());
            if ($vendorOrder->getVendorId()) {
                $creditmemo->setVendorId($vendorOrder->getVendorId());
                $creditmemo->setVendorOrder($vendorOrder);
            }
        } elseif ($orderId) {
            $vendorOrder = $this->getVendorOrder();
            $data = $this->getCreditmemo();
            $invoice = $this->_initVendorInvoice($vendorOrder);

            if (!$this->_canCreditmemo($vendorOrder)) {
                return false;
            }

            $savedData = $this->_getItemData();

            $qtys = [];
            $backToStock = [];
            foreach ($savedData as $orderItemId => $itemData) {
                if (isset($itemData['qty'])) {
                    $qtys[$orderItemId] = $itemData['qty'];
                }
                if (isset($itemData['back_to_stock'])) {
                    $backToStock[$orderItemId] = true;
                }
            }
            $data['qtys'] = $qtys;

            if ($invoice) {
                $creditmemo = $this->creditmemoFactory->createByVendorInvoice($invoice, $data);
            } else {
                $creditmemo = $this->creditmemoFactory->createByVendorOrder($vendorOrder, $data);
            }
            //  return false;
            /**
             * Process back to stock flags
             */
            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                $orderItem = $creditmemoItem->getOrderItem();
                $parentId = $orderItem->getParentItemId();
                if (isset($backToStock[$orderItem->getId()])) {
                    $creditmemoItem->setBackToStock(true);
                } elseif ($orderItem->getParentItem() && isset($backToStock[$parentId]) && $backToStock[$parentId]) {
                    $creditmemoItem->setBackToStock(true);
                } elseif (empty($savedData)) {
                    $creditmemoItem->setBackToStock(
                        $this->stockConfiguration->isAutoReturnEnabled()
                    );
                } else {
                    $creditmemoItem->setBackToStock(false);
                }
            }
            if ($vendorOrder->getVendorId()) {
                $creditmemo->setVendorId($vendorOrder->getVendorId());
                $creditmemo->setVendorOrder($vendorOrder);
            }
        }

        $this->eventManager->dispatch(
            'vendors_sales_order_creditmemo_register_before',
            ['creditmemo' => $creditmemo, 'input' => $this->getCreditmemo()]
        );

        $this->registry->register('current_creditmemo', $creditmemo);

        return $creditmemo;
    }
}
