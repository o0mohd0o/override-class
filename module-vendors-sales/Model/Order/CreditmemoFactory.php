<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Model\Order;

use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Bundle\Ui\DataProvider\Product\Listing\Collector\BundlePrice;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Factory class for @see \Magento\Sales\Api\Data\ShipmentInterface
 */
class CreditmemoFactory extends \Magento\Sales\Model\Order\CreditmemoFactory
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var JsonSerializer
     */
    private $serializer;

    /**
     * CreditmemoFactory constructor.
     * @param \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param JsonSerializer|null $serializer
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param JsonSerializer $json
     */
    public function __construct(
        \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Magento\Framework\Module\Manager $moduleManager,
        JsonSerializer $json
    ) {
        parent::__construct($convertOrderFactory, $taxConfig, $serializer);
        $this->moduleManager = $moduleManager;
        $this->serializer = $json;
    }

    /**
     * Prepare order creditmemo based on order items and requested params
     *
     * @param \Vnecoms\VendorsSales\Model\Order $order
     * @param array $data
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function createByVendorOrder(\Vnecoms\VendorsSales\Model\Order $vendorOrder, array $data = [])
    {
        $order = $vendorOrder->getOrder();
        $totalQty = 0;
        $creditmemo = $this->convertor->toCreditmemo($order);

        if ($this->moduleManager->isEnabled("Vnecoms_VendorsShipping")) {
            if (!isset($data['shipping_amount'])) {
                $baseAllowedAmount = $this->getVendorShippingAmountByOrder($vendorOrder);
                $creditmemo->setBaseShippingAmount($baseAllowedAmount);
                $creditmemo->setBaseShippingInclTax($baseAllowedAmount);
            }
            // to do something
        } else {
            $creditmemo->setShippingAmount(0);
            $creditmemo->setBaseShippingAmount(0);
            $creditmemo->setShippingTaxAmount(0);
            $creditmemo->setBaseShippingInclTax(0);
            $creditmemo->setBaseShippingTaxAmount(0);
            $creditmemo->setShippingInclTax(0);
        }

        $qtys = isset($data['qtys']) ? $data['qtys'] : [];

        foreach ($order->getAllItems() as $orderItem) {
            if (!$this->canRefundItem($orderItem, $qtys)) {
                continue;
            }

            $item = $this->convertor->itemToCreditmemoItem($orderItem);
            if ($orderItem->isDummy()) {
                $qty = 1;
                $orderItem->setLockedDoShip(true);
            } else {
                if (isset($qtys[$orderItem->getId()])) {
                    $qty = (double)$qtys[$orderItem->getId()];
                } elseif (!count($qtys)) {
                    $qty = $orderItem->getQtyToRefund();
                } else {
                    continue;
                }
            }
            if ($vendorOrder->getVendorId() != $orderItem->getVendorId()) {
                $qty = 0;
            }
            $totalQty += $qty;
            $item->setQty($qty);
            $creditmemo->addItem($item);
        }

        $creditmemo->setVendorOrderId($vendorOrder->getId());
        $creditmemo->setTotalQty($totalQty);
        $this->initData($creditmemo, $data);
        $creditmemo->collectTotals();
        return $creditmemo;
    }

    /**
     * @param \Vnecoms\VendorsSales\Model\Order\Invoice $vendorInvoice
     * @param array $data
     * @return mixed
     */
    public function createByVendorInvoice(Invoice $vendorInvoice, array $data = [])
    {
        $vendorOrder = $vendorInvoice->getVendorOrder();
        $order = $vendorOrder->getOrder();
        $totalQty = 0;
        $qtyList = isset($data['qtys']) ? $data['qtys'] : [];
        $creditmemo = $this->convertor->toCreditmemo($order);
        $invoice = $vendorInvoice->getInvoice();
        $creditmemo->setInvoice($invoice);

        $invoiceRefundLimitsQtyList = $this->getInvoiceRefundLimitsQtyList($vendorInvoice);

        foreach ($vendorInvoice->getAllItems() as $invoiceItem) {
            /** @var OrderItemInterface $orderItem */
            $orderItem = $invoiceItem->getOrderItem();

            if (!$this->canRefundItem($orderItem, $qtyList, $invoiceRefundLimitsQtyList)) {
                continue;
            }

            $qty = min(
                $this->getQtyToRefund($orderItem, $qtyList, $invoiceRefundLimitsQtyList),
                $invoiceItem->getQty()
            );
            $totalQty += $qty;
            $item = $this->convertor->itemToCreditmemoItem($orderItem);
            $item->setQty($qty);
            $creditmemo->addItem($item);
        }
        $creditmemo->setTotalQty($totalQty);

        $this->initData($creditmemo, $data);


        if ($this->moduleManager->isEnabled("Vnecoms_VendorsShipping")) {
            if (!isset($data['shipping_amount'])) {
                $baseAllowedAmount = $this->getVendorShippingAmount($vendorInvoice);
                $creditmemo->setBaseShippingAmount($baseAllowedAmount);
                $creditmemo->setBaseShippingInclTax($baseAllowedAmount);
            }
        } else {
            $creditmemo->setShippingAmount(0);
            $creditmemo->setBaseShippingAmount(0);
            $creditmemo->setShippingTaxAmount(0);
            $creditmemo->setBaseShippingInclTax(0);
            $creditmemo->setBaseShippingTaxAmount(0);
            $creditmemo->setShippingInclTax(0);
        }
        $creditmemo->setVendorOrderId($vendorOrder->getId());
        $creditmemo->collectTotals();
        return $creditmemo;
    }


    /**
     * Gets quantity of items to refund based on order item.
     *
     * @param Item $orderItem
     * @param array $qtyList
     * @param array $refundLimits
     * @return float
     */
    private function getQtyToRefund(Item $orderItem, array $qtyList, array $refundLimits = []): float
    {
        $qty = 0;
        if ($orderItem->isDummy()) {
            if (isset($qtyList[$orderItem->getParentItemId()])) {
                $parentQty = $qtyList[$orderItem->getParentItemId()];
            } elseif ($orderItem->getProductType() === BundlePrice::PRODUCT_TYPE) {
                $parentQty = $orderItem->getQtyInvoiced();
            } else {
                $parentQty = $orderItem->getParentItem() ? $orderItem->getParentItem()->getQtyToRefund() : 1;
            }
            $qty = $this->calculateProductOptions($orderItem, $parentQty);
        } else {
            if (isset($qtyList[$orderItem->getId()])) {
                $qty = $qtyList[$orderItem->getId()];
            } elseif (!count($qtyList)) {
                $qty = $orderItem->getQtyToRefund();
            } else {
                return (float)$qty;
            }

            if (isset($refundLimits[$orderItem->getId()])) {
                $qty = min($qty, $refundLimits[$orderItem->getId()]);
            }
        }

        return (float)$qty;
    }


    /**
     * Calculate product options.
     *
     * @param Item $orderItem
     * @param int $parentQty
     * @return int
     */
    private function calculateProductOptions(Item $orderItem, int $parentQty): int
    {
        $qty = $parentQty;
        $productOptions = $orderItem->getProductOptions();
        if (isset($productOptions['bundle_selection_attributes'])) {
            $bundleSelectionAttributes = $this->serializer->unserialize(
                $productOptions['bundle_selection_attributes']
            );
            if ($bundleSelectionAttributes) {
                $qty = $bundleSelectionAttributes['qty'] * $parentQty;
            }
        }
        return $qty;
    }

    /**
     * @param \Vnecoms\VendorsSales\Model\Order\Invoice $invoice
     * @return array
     */
    private function getInvoiceRefundedQtyList(Invoice $vendorInvoice): array
    {
        $invoiceRefundedQtyList = [];
        foreach ($vendorInvoice->getVendorOrder()->getCreditmemoCollection() as $creditmemo) {
            if ($creditmemo->getState() !== Creditmemo::STATE_CANCELED &&
                $creditmemo->getInvoiceId() === $vendorInvoice->getInvoiceId()
            ) {
                foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                    $orderItemId = $creditmemoItem->getOrderItem()->getId();
                    if (isset($invoiceRefundedQtyList[$orderItemId])) {
                        $invoiceRefundedQtyList[$orderItemId] += $creditmemoItem->getQty();
                    } else {
                        $invoiceRefundedQtyList[$orderItemId] = $creditmemoItem->getQty();
                    }
                }
            }
        }

        return $invoiceRefundedQtyList;
    }

    /**
     * @param \Vnecoms\VendorsSales\Model\Order\Invoice $vendorInvoice
     * @return array
     */
    private function getInvoiceRefundLimitsQtyList(Invoice $vendorInvoice): array
    {
        $invoiceRefundLimitsQtyList = [];
        $invoiceRefundedQtyList = $this->getInvoiceRefundedQtyList($vendorInvoice);

        foreach ($vendorInvoice->getAllItems() as $invoiceItem) {
            $qtyCanBeRefunded = $invoiceItem->getQty();
            $orderItemId = $invoiceItem->getOrderItem()->getId();
            if (isset($invoiceRefundedQtyList[$orderItemId])) {
                $qtyCanBeRefunded = $qtyCanBeRefunded - $invoiceRefundedQtyList[$orderItemId];
            }
            $invoiceRefundLimitsQtyList[$orderItemId] = $qtyCanBeRefunded;
        }

        return $invoiceRefundLimitsQtyList;
    }

    /**
     * @param \Vnecoms\VendorsSales\Model\Order\Invoice $vendorInvoice
     * @return float
     */
    private function getVendorShippingAmount(\Vnecoms\VendorsSales\Model\Order\Invoice $vendorInvoice): float
    {
        $vendorOrder = $vendorInvoice->getVendorOrder();
        $order = $vendorOrder->getOrder();
        $isShippingInclTax = $this->taxConfig->displaySalesShippingInclTax($order->getStoreId());
        if ($isShippingInclTax) {
            $amount = $vendorOrder->getBaseShippingInclTax() -
                $vendorOrder->getBaseShippingRefunded() -
                $vendorOrder->getBaseShippingTaxRefunded();
        } else {
            $amount = $vendorOrder->getBaseShippingAmount() - $vendorOrder->getBaseShippingRefunded();
            $amount = min($amount, $vendorInvoice->getBaseShippingAmount());
        }

        return (float)$amount;
    }

    /**
     * @param \Vnecoms\VendorsSales\Model\Order $vendorOrder
     * @return float
     */
    private function getVendorShippingAmountByOrder(\Vnecoms\VendorsSales\Model\Order $vendorOrder): float
    {
        $order = $vendorOrder->getOrder();
        $isShippingInclTax = $this->taxConfig->displaySalesShippingInclTax($order->getStoreId());
        if ($isShippingInclTax) {
            $amount = $vendorOrder->getBaseShippingInclTax() -
                $vendorOrder->getBaseShippingRefunded() -
                $vendorOrder->getBaseShippingTaxRefunded();
        } else {
            $amount = $vendorOrder->getBaseShippingAmount() - $vendorOrder->getBaseShippingRefunded();
            $amount = min($amount, $vendorOrder->getBaseShippingAmount());
        }

        return (float)$amount;
    }
}
