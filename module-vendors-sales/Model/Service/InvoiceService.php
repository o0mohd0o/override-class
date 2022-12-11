<?php
/**
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Vnecoms\VendorsSales\Model\Order;

class InvoiceService extends \Magento\Sales\Model\Service\InvoiceService
{
    /**
     * @param Order $order
     * @param array $orderItemsQtyToInvoice
     * @return \Magento\Sales\Model\Order\Invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareVendorInvoice(Order $vendorOrder, array $orderItemsQtyToInvoice = [])
    {
        $isQtysEmpty = empty($orderItemsQtyToInvoice);
        $order = $vendorOrder->getOrder();
        $invoice = $this->orderConverter->toInvoice($order);
        $totalQty = 0;
        $vendorOrderItems = $vendorOrder->getAllItems();
        $preparedItemsQty = $this->prepareItemsQty($order, $orderItemsQtyToInvoice);

        foreach ($order->getAllItems() as $orderItem) {
            if (!$this->canInvoiceItem($orderItem, $preparedItemsQty)) {
                continue;
            }

            if ($orderItem->isDummy()) {
                $qty = $orderItem->getQtyOrdered() ? $orderItem->getQtyOrdered() : 1;
            } elseif (isset($preparedItemsQty[$orderItem->getId()])) {
                $qty = $preparedItemsQty[$orderItem->getId()];
            } elseif ($isQtysEmpty) {
                $qty = $orderItem->getQtyToInvoice();
            } else {
                $qty = 0;
            }
            if (!isset($vendorOrderItems[$orderItem->getId()])) {
                $qty = 0;
            }
            $item = $this->orderConverter->itemToInvoiceItem($orderItem);
            $totalQty += $qty;
            $this->setInvoiceItemQuantity($item, $qty);
            $invoice->addItem($item);
        }

        $invoice->setVendorOrder($vendorOrder);
        $invoice->setTotalQty($totalQty);
        $invoice->collectTotals();
        $this->reloadDiscount($invoice, $vendorOrder);
        $order->getInvoiceCollection()->addItem($invoice);
        
        return $invoice;
    }

    /**
     * @param $invoice
     * @param $vendorOrder
     */
    protected function reloadDiscount($invoice, $vendorOrder) {
        $previewTaxAmount = 0 ;
        $previewBaseTaxAmount = 0 ;
        $previewShippingTaxAmount = 0 ;
        $previewBaseShippingTaxAmount = 0 ;

        $isCreatedInvoiceForShipping = $this->isShippingDiscount($vendorOrder);

        // shipping amount
        $vendorShippingAmount = $isCreatedInvoiceForShipping?0:$vendorOrder->getShippingAmount();
        $baseVendorShippingAmount = $isCreatedInvoiceForShipping?0:$vendorOrder->getBaseShippingAmount();
        $discountShippingAmount = $isCreatedInvoiceForShipping?0:$vendorOrder->getShippingDiscountAmount();
        $baseDiscountShippingAmount = $isCreatedInvoiceForShipping?0:$vendorOrder->getBaseShippingDiscountAmount();

        $discountShippingAmount = $discountShippingAmount ? $discountShippingAmount : 0;
        $baseDiscountShippingAmount = $baseDiscountShippingAmount ? $baseDiscountShippingAmount : 0;

        $beforeShippingAmount = $invoice->getShippingAmount();
        $baseBeforeShippingAmount = $invoice->getBaseShippingAmount();

        $invoice->setShippingAmount($vendorShippingAmount);
        $invoice->setBaseShippingAmount($baseVendorShippingAmount);

        // shipping tax amount
        $vendorShippingTaxmount = $vendorOrder->getShippingTaxAmount() - $previewShippingTaxAmount > 0 ?
            $vendorOrder->getShippingTaxAmount() - $previewShippingTaxAmount : 0;
        $baseVendorShippingTaxAmount = $vendorOrder->getBaseShippingTaxAmount() - $previewBaseShippingTaxAmount > 0 ?
            $vendorOrder->getBaseShippingTaxAmount() - $previewBaseShippingTaxAmount : 0;
        ;

        $vendorTaxmount = $invoice->getTaxAmount() - $invoice->getShippingTaxAmount()
            +$vendorShippingTaxmount;


        $baseVendorTaxAmount = $invoice->getBaseTaxAmount() - $invoice->getBaseShippingTaxAmount()
            +$baseVendorShippingTaxAmount;
        ;

        $beforeShippingTaxAmount = $invoice->getShippingTaxAmount();
        $baseBeforeShippingTaxAmount = $invoice->getBaseShippingTaxAmount();


        $invoice->setShippingTaxAmount($vendorShippingTaxmount);
        $invoice->setBaseShippingTaxAmount($baseVendorShippingTaxAmount);

        $beforeDiscountAmount = $invoice->getDiscountAmount();
        $baseBeforeDiscountAmount = $invoice->getBaseDiscountAmount();

        $invoice->setDiscountAmount(
            $invoice->getDiscountAmount()
            +  $vendorOrder->getShippingDiscountAmount()
            - $discountShippingAmount
        );
        $invoice->setBaseDiscountAmount(  $invoice->getBaseDiscountAmount()
            +  $vendorOrder->getBaseShippingDiscountAmount()
            - $baseDiscountShippingAmount );


        /*
        // tax amount
        $vendorTaxmount = $vendorOrder->getTaxAmount() - $previewTaxAmount > 0 ?
            $vendorOrder->getTaxAmount() - $previewTaxAmount : 0;
        $baseVendorTaxAmount = $vendorOrder->getBaseTaxAmount() - $previewBaseTaxAmount > 0 ?
            $vendorOrder->getBaseTaxAmount() - $previewBaseTaxAmount : 0;
        ;
          */
        $invoice->setTaxAmount($vendorTaxmount);
        $invoice->setBaseTaxAmount($baseVendorTaxAmount);


        // set grandtotal and base grantotal for invoice
        $grandTotal = $invoice->getGrandTotal()
            -  $beforeDiscountAmount
            - $beforeShippingAmount - $beforeShippingTaxAmount
            + $vendorShippingAmount + $vendorShippingTaxmount
            + $invoice->getDiscountAmount()
        ;
        $baseGrandTotal = $invoice->getBaseGrandTotal()
            -  $baseBeforeDiscountAmount
            - $baseBeforeShippingAmount
            - $baseBeforeShippingTaxAmount
            + $baseVendorShippingAmount + $baseVendorShippingTaxAmount
            + $invoice->getBaseDiscountAmount()
        ;
        $invoice->setDiscountDescription($vendorOrder->getDiscountDescription());

        /*
        $totalCreditAmount = $invoice->getCreditAmount();
        $baseTotalCreditAmount = $invoice->getBaseCreditAmount();
        $invoice->setCreditAmount(0);
        $invoice->setBaseCreditAmount(0);
        $invoice->setGrandTotal($grandTotal - $totalCreditAmount);
        $invoice->setBaseGrandTotal($baseGrandTotal - $baseTotalCreditAmount);
        */

        $invoice->setGrandTotal($grandTotal);
        $invoice->setBaseGrandTotal($baseGrandTotal);
    }

    /**
     * @param $vendorOrder
     * @return bool
     */
    private function isShippingDiscount($vendorOrder): bool
    {
        $addShippingDiscount = false;
        foreach ($vendorOrder->getInvoiceCollection() as $previousInvoice) {
            if ($previousInvoice->getDiscountAmount() && !$previousInvoice->isCanceled()) {
                $addShippingDiscount = true;
            }
        }
        return $addShippingDiscount;
    }
    
    /**
     * Check if order item can be invoiced.
     *
     * @param OrderItemInterface $item
     * @param array $qtys
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function canInvoiceItem(OrderItemInterface $item, array $qtys)
    {
        if ($item->getLockedDoInvoice()) {
            return false;
        }
        if ($item->isDummy()) {
            if ($item->getHasChildren()) {
                foreach ($item->getChildrenItems() as $child) {
                    if (empty($qtys)) {
                        if ($child->getQtyToInvoice() > 0) {
                            return true;
                        }
                    } else {
                        if (isset($qtys[$child->getId()]) && $qtys[$child->getId()] > 0) {
                            return true;
                        }
                    }
                }
                return false;
            } elseif ($item->getParentItem()) {
                $parent = $item->getParentItem();
                if (empty($qtys)) {
                    return $parent->getQtyToInvoice() > 0;
                } else {
                    return isset($qtys[$parent->getId()]) && $qtys[$parent->getId()] > 0;
                }
            }
        } else {
            return $item->getQtyToInvoice() > 0;
        }
    }

    /**
     * Prepare qty to invoice for parent and child products if theirs qty is not specified in initial request.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $orderItemsQtyToInvoice
     * @return array
     */
    private function prepareItemsQty(
        \Magento\Sales\Model\Order $order,
        array $orderItemsQtyToInvoice
    ) {
        foreach ($order->getAllItems() as $orderItem) {
            if (isset($orderItemsQtyToInvoice[$orderItem->getId()])) {
                if ($orderItem->isDummy() && $orderItem->getHasChildren()) {
                    $orderItemsQtyToInvoice = $this->setChildItemsQtyToInvoice($orderItem, $orderItemsQtyToInvoice);
                }
            } else {
                if (isset($orderItemsQtyToInvoice[$orderItem->getParentItemId()])) {
                    $orderItemsQtyToInvoice[$orderItem->getId()] =
                        $orderItemsQtyToInvoice[$orderItem->getParentItemId()];
                }
            }
        }

        return $orderItemsQtyToInvoice;
    }

    /**
     * Sets qty to invoice for children order items, if not set.
     *
     * @param OrderItemInterface $parentOrderItem
     * @param array $orderItemsQtyToInvoice
     * @return array
     */
    private function setChildItemsQtyToInvoice(
        OrderItemInterface $parentOrderItem,
        array $orderItemsQtyToInvoice
    ) {
        /** @var OrderItemInterface $childOrderItem */
        foreach ($parentOrderItem->getChildrenItems() as $childOrderItem) {
            if (!isset($orderItemsQtyToInvoice[$childOrderItem->getItemId()])) {
                $productOptions = $childOrderItem->getProductOptions();

                if (isset($productOptions['bundle_selection_attributes']) &&
                    class_exists('Magento\Framework\Serialize\Serializer\Json')) {
                    $jsonSerializer = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Serialize\Serializer\Json');
                    $bundleSelectionAttributes = $jsonSerializer
                        ->unserialize($productOptions['bundle_selection_attributes']);
                    $orderItemsQtyToInvoice[$childOrderItem->getItemId()] =
                        $bundleSelectionAttributes['qty'] * $orderItemsQtyToInvoice[$parentOrderItem->getItemId()];
                } elseif (isset($productOptions['bundle_selection_attributes']) &&
                    !class_exists('Magento\Framework\Serialize\Serializer\Json')) {
                    $bundleSelectionAttributes = unserialize($productOptions['bundle_selection_attributes']);
                    $orderItemsQtyToInvoice[$childOrderItem->getItemId()] =
                        $bundleSelectionAttributes['qty'] * $orderItemsQtyToInvoice[$parentOrderItem->getItemId()];
                }
            }
        }

        return $orderItemsQtyToInvoice;
    }

    /**
     * Set quantity to invoice item.
     *
     * @param InvoiceItemInterface $item
     * @param float $qty
     * @return $this
     * @throws LocalizedException
     */
    protected function setInvoiceItemQuantity(InvoiceItemInterface $item, $qty)
    {
        $qty = ($item->getOrderItem()->getIsQtyDecimal()) ? (double) $qty : (int) $qty;
        $qty = $qty > 0 ? $qty : 0;

        /**
         * Check qty availability
         */
        $qtyToInvoice = sprintf("%F", $item->getOrderItem()->getQtyToInvoice());
        $qty = sprintf("%F", $qty);
        if ($qty > $qtyToInvoice && !$item->getOrderItem()->isDummy()) {
            throw new LocalizedException(
                __('We found an invalid quantity to invoice item "%1".', $item->getName())
            );
        }

        $item->setQty($qty);

        return $this;
    }
}
