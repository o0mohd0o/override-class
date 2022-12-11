<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Model\Order\Creditmemo\Total;

use Magento\Tax\Model\Config;
use Magento\Framework\App\ObjectManager;

/**
 * Discount total calculator
 */
class Discount extends \Magento\Sales\Model\Order\Creditmemo\Total\Discount
{
    /**
     * @var Config
     */
    private $taxConfig;

    /**
     * @param Config $taxConfig
     * @param array $data
     */
    public function __construct(
        Config $taxConfig,
        array $data = []
    ) {
        $this->taxConfig = $taxConfig;

        parent::__construct($taxConfig, $data);
    }

    /**
     * Collect discount
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $vendorOrder = $creditmemo->getVendorOrder();
        if (!$vendorOrder) {
            $vendorOrderId = $creditmemo->getVendorOrderId();
            $vendorOrder = ObjectManager::getInstance()->create("\Vnecoms\VendorsSales\Model\Order")
                ->load($vendorOrderId);
        }

        if (!$vendorOrder || !$vendorOrder->getId()) {
            return parent::collect($creditmemo);
        }

        $creditmemo->setDiscountAmount(0);
        $creditmemo->setBaseDiscountAmount(0);

        $order = $creditmemo->getOrder();

        $totalDiscountAmount = 0;
        $baseTotalDiscountAmount = 0;

        /**
         * Calculate how much shipping discount should be applied
         * basing on how much shipping should be refunded.
         */
        $baseShippingAmount = $this->getBaseShippingAmount($creditmemo);

        /**
         * If credit memo's shipping amount is set and Order's shipping amount is 0,
         * throw exception with different message
         */
        if ($baseShippingAmount && $vendorOrder->getBaseShippingAmount() <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("You can not refund shipping if there is no shipping amount.")
            );
        }
        if ($baseShippingAmount) {
            $orderBaseShippingAmount =  $this->isShippingInclTax((int)$order->getStoreId()) ?
                $vendorOrder->getBaseShippingInclTax() : $vendorOrder->getBaseShippingAmount();
            $orderShippingAmount =  $this->isShippingInclTax((int)$order->getStoreId()) ?
                $vendorOrder->getShippingInclTax() : $vendorOrder->getShippingAmount();
            $baseShippingDiscount = $baseShippingAmount *
                $vendorOrder->getBaseShippingDiscountAmount() /
                $orderBaseShippingAmount;
            $shippingDiscount = $orderShippingAmount * $baseShippingDiscount / $orderBaseShippingAmount;
            $totalDiscountAmount = $totalDiscountAmount + $shippingDiscount;
            $baseTotalDiscountAmount = $baseTotalDiscountAmount + $baseShippingDiscount;
        }

        /** @var $item \Magento\Sales\Model\Order\Invoice\Item */
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();

            if ($orderItem->isDummy()) {
                continue;
            }

            $orderItemDiscount = (double)$orderItem->getDiscountInvoiced();
            $baseOrderItemDiscount = (double)$orderItem->getBaseDiscountInvoiced();
            $orderItemQty = $orderItem->getQtyInvoiced();

            if ($orderItemDiscount && $orderItemQty) {
                $discount = $orderItemDiscount - $orderItem->getDiscountRefunded();
                $baseDiscount = $baseOrderItemDiscount - $orderItem->getBaseDiscountRefunded();
                if (!$item->isLast()) {
                    $availableQty = $orderItemQty - $orderItem->getQtyRefunded();
                    $discount = $creditmemo->roundPrice($discount / $availableQty * $item->getQty(), 'regular', true);
                    $baseDiscount = $creditmemo->roundPrice(
                        $baseDiscount / $availableQty * $item->getQty(),
                        'base',
                        true
                    );
                }

                $item->setDiscountAmount($discount);
                $item->setBaseDiscountAmount($baseDiscount);

                $totalDiscountAmount += $discount;
                $baseTotalDiscountAmount += $baseDiscount;
            }
        }

        $creditmemo->setDiscountAmount(-$totalDiscountAmount);
        $creditmemo->setBaseDiscountAmount(-$baseTotalDiscountAmount);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $totalDiscountAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $baseTotalDiscountAmount);
        return $this;
    }

    /**
     * Get base shipping amount
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return float
     */
    private function getBaseShippingAmount(\Magento\Sales\Model\Order\Creditmemo $creditmemo): float
    {
        $baseShippingAmount = (float)$creditmemo->getBaseShippingAmount();
        if (!$baseShippingAmount) {
            $baseShippingInclTax = (float)$creditmemo->getBaseShippingInclTax();
            $baseShippingTaxAmount = (float)$creditmemo->getBaseShippingTaxAmount();
            $baseShippingAmount = $this->isShippingInclTax((int)$creditmemo->getStoreId()) ?
                $baseShippingInclTax : $baseShippingInclTax - $baseShippingTaxAmount;
        }
        return $baseShippingAmount;
    }

    /**
     * Returns whether the user specified a shipping amount that already includes tax
     *
     * @param int $storeId
     * @return bool
     */
    private function isShippingInclTax(int $storeId): bool
    {
        return (bool)$this->taxConfig->displaySalesShippingInclTax($storeId);
    }
}
