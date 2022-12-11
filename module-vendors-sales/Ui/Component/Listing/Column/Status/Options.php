<?php
namespace Vnecoms\VendorsSales\Ui\Component\Listing\Column\Status;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

/**
 * Class Options
 */
class Options extends \Magento\Sales\Ui\Component\Listing\Column\Status\Options
{
    /**
     * @var array
     */
    protected $newOptions;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->newOptions === null) {
            $options = parent::toOptionArray();

            foreach($options as $key=>$option){
                $options[$key]['label'] = __($option['label']);
            }
            $this->newOptions = $options;
        }
        return $this->newOptions;
    }
}
