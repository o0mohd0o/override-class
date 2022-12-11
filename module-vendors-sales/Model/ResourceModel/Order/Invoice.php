<?php
/**
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsSales\Model\ResourceModel\Order;

/**
 * Cms page mysql resource
 */
class Invoice extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ves_vendor_sales_invoice', 'entity_id');
    }
    
    /**
     * Update vendor order id for order items.
     *
     * @param \Magento\Framework\DataObject $object
     * @return $this
     */
    
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);

        if($object->getItems() && is_array($object->getItems()) && sizeof($object->getItems()) && $object->getId() ){
            foreach($object->getItems() as $invoiceItem){
                $invoiceItem->setVendorInvoiceId($object->getId());
            }
        }

        return $this;
    }
    
    /**
     * Check if the vendor invoice is created by base invoice id.
     * @param int $invoiceId
     * @return boolean
     */
    public function isCreatedVendorInvoice($invoiceId){
        $table = $this->getTable('ves_vendor_sales_invoice');
        $readCollection = $this->getConnection();
        $select = $readCollection->select();
        $select->from(
            $table,
            ['invoice_num' => 'count(entity_id)']
        )->where(
            'invoice_id = :invoice_id'
        );
        $bind = ['invoice_id' => $invoiceId];

        $count = $readCollection->fetchOne($select, $bind);
        return $count > 0;
    }
}
