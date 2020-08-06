<?php
/**
 * Created by PhpStorm.
 * User: joshuacarter
 * Date: 13/08/2018
 * Time: 15:06
 */
declare(strict_types=1);

namespace Interjar\ConfigurableChildVisibility\Plugin\Block\Product\View\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as Subject;

use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class Configurable
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    protected $jsonEncoder;
    protected $jsonDecoder;
    protected $stockRegistry;

    /**
     * Configurable constructor
     *
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,

		EncoderInterface $jsonEncoder,
        		DecoderInterface $jsonDecoder,
	 	StockRegistryInterface $stockRegistry
    ) {
        $this->stockConfiguration = $stockConfiguration;

		$this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Get All used products for configurable
     *
     * @param Subject $subject
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeGetAllowProducts(
        Subject $subject
    ) {
        if (!$subject->hasAllowProducts() &&
            $this->stockConfiguration->isShowOutOfStock()) {
            /** @var Product $product */
            $product = $subject->getProduct();
            $allowProducts = [];
            $usedProducts = $product->getTypeInstance(true)
                ->getUsedProducts($product);
            /** @var Product $usedProduct */
            foreach ($usedProducts as $usedProduct) {
                if ($usedProduct->getStatus() == Status::STATUS_ENABLED) {
                    $allowProducts[] = $usedProduct;
                }
            }
            $subject->setAllowProducts($allowProducts);
        }
        return $subject->getData('allow_products');
    }

    //DABEE FIX START
    // Adding Quantitites for Storefront
    public function aroundGetJsonConfig(
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject,
        \Closure $proceed
    )
    {
        $quantities = [];
        $config = $proceed();
        $config = $this->jsonDecoder->decode($config);

        foreach ($subject->getAllowProducts() as $product) {
            $stockitem = $this->stockRegistry->getStockItem(
                $product->getId(),
                $product->getStore()->getWebsiteId()
            );
            $quantities[$product->getId()] = $stockitem->getQty();
        }

        $config['quantities'] = $quantities;

        return $this->jsonEncoder->encode($config);
    }
    //DABEE FIX END

}
