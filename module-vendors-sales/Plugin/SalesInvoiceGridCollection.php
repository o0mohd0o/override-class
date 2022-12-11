<?php

namespace Vnecoms\VendorsSales\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Vnecoms\VendorsSales\Model\ResourceModel\Order\AdminInvoice\Grid\Collection as SalesOrderInvoiceGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Grid\Collection as SalesOrderShipmentGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection as SalesOrderCreditmemoGridCollection;

class SalesInvoiceGridCollection
{
    const SALES_ORDER_INVOICE_GRID_DATA_SOURCE = 'sales_order_invoice_grid_data_source';
    const SALES_ORDER_SHIPMENT_GRID_DATA_SOURCE = 'sales_order_shipment_grid_data_source';
    const SALES_ORDER_CREDITMEMO_GRID_DATA_SOURCE = 'sales_order_creditmemo_grid_data_source';

    /**
     * @var SalesOrderInvoiceGridCollection
     */
    private $collection;

    /**
     * @var SalesOrderShipmentGridCollection
     */
    private $shipmentCollection;

    /**
     * @var SalesOrderCreditmemoGridCollection
     */
    private $creditmemoCollection;

    /**
     * SalesInvoiceGridCollection constructor.
     * @param SalesOrderInvoiceGridCollection $collection
     * @param SalesOrderShipmentGridCollection $shipmentCollection
     * @param SalesOrderCreditmemoGridCollection $creditmemoCollection
     */
    public function __construct(
        SalesOrderInvoiceGridCollection $collection,
        SalesOrderShipmentGridCollection $shipmentCollection,
        SalesOrderCreditmemoGridCollection $creditmemoCollection
    )
    {
        $this->collection = $collection;
        $this->shipmentCollection = $shipmentCollection;
        $this->creditmemoCollection = $creditmemoCollection;
    }

    /**
     * @param CollectionFactory $subject
     * @param \Closure $proceed
     * @param $requestName
     * @return mixed|SalesOrderInvoiceGridCollection
     */
    public function aroundGetReport(CollectionFactory $subject, \Closure $proceed, $requestName)
    {
        $result = $proceed($requestName);

        if (self::SALES_ORDER_INVOICE_GRID_DATA_SOURCE == $requestName) {
            if ($result instanceof $this->collection) {

                $select = $this->collection->getSelect();
                $select->joinLeft(
                    ['si' => $this->collection->getTable('ves_vendor_sales_invoice')],
                    'main_table.entity_id = si.invoice_id',
                    []
                );
                $select->joinLeft(
                    ['vendor_table' => $this->collection->getTable('ves_vendor_entity')],
                    'si.vendor_id = vendor_table.entity_id',
                    ['vendor' => "vendor_table.vendor_id"]
                )->group( "main_table.entity_id");
                $arrayMaps = ['entity_id', 'increment_id', 'state', 'created_at', 'base_grand_total', 'grand_total'];
                foreach ($arrayMaps as $map) {
                    $this->collection->addFilterToMap($map, 'main_table.'.$map);
                }
                return $this->collection;
            }
        }

        if (self::SALES_ORDER_SHIPMENT_GRID_DATA_SOURCE == $requestName) {
            if ($result instanceof $this->shipmentCollection) {
                $select = $this->shipmentCollection->getSelect();
                $select->joinLeft(
                    ['vendor_table' => $this->shipmentCollection->getTable('ves_vendor_entity')],
                    'main_table.vendor_id = vendor_table.entity_id',
                    ['vendor' => "vendor_table.vendor_id"]
                )->group( "main_table.entity_id");
                $this->shipmentCollection->addFilterToMap('vendor', 'vendor_table.vendor_id');
                $arrayMaps = ['entity_id', 'increment_id', 'state', 'created_at'];
                foreach ($arrayMaps as $map) {
                    $this->shipmentCollection->addFilterToMap($map, 'main_table.'.$map);
                }

                return $this->shipmentCollection;
            }
        }


        if (self::SALES_ORDER_CREDITMEMO_GRID_DATA_SOURCE == $requestName) {
            if ($result instanceof $this->creditmemoCollection) {
                $select = $this->creditmemoCollection->getSelect();
                $select->joinLeft(
                    ['si' => $this->collection->getTable('ves_vendor_sales_order')],
                    'main_table.vendor_order_id = si.entity_id',
                    []
                );
                $select->joinLeft(
                    ['vendor_table' => $this->creditmemoCollection->getTable('ves_vendor_entity')],
                    'si.vendor_id = vendor_table.entity_id',
                    ['vendor' => "vendor_table.vendor_id"]
                )->group( "main_table.entity_id");
                $this->creditmemoCollection->addFilterToMap('vendor', 'vendor_table.vendor_id');
                $arrayMaps = ['entity_id', 'increment_id', 'created_at', 'base_grand_total', 'state'];
                foreach ($arrayMaps as $map) {
                    $this->creditmemoCollection->addFilterToMap($map, 'main_table.'.$map);
                }
                return $this->creditmemoCollection;
            }
        }

        return $result;
    }
}