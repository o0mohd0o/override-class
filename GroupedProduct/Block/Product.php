<?php
namespace Bnaia\GroupedProduct\Block;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Page\Config;
use Magento\Framework\Registry;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product as ProductCollection;


class Product extends Template
{
    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Registry
     */
    private $_registry;

    /**
     * @param Context $context
     * @param Config $pageConfig
     * @param Registry $registry
     * @param array $data
     */
    protected $priceHelper;
    public function __construct(
        Context $context,
        Config $pageConfig,
        Data $priceHelper,
        Registry $registry,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        ProductCollection $productCollection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->pageConfig = $pageConfig;
        $this->_registry = $registry;
        $this->priceHelper = $priceHelper;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->productCollection = $productCollection;
    }

    /**
     * @param $price
     * @return float|string
     */

    public function getFormattedPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * @param $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function getProductBySku($sku) {
        return $this->productRepository->get($sku);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreMediaUrl() {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * @return \Magento\Directory\Model\Currency
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreCurrency() {
        return $this->storeManager->getStore()->getCurrentCurrency();
    }

    public function getCurrentProduct($id) {
        return $this->productCollection->load($id);
    }
}

