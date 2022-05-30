<?php

namespace App\Message;

final class AddShopifyHooks
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    private $shopId;

    private $categoryUrl;

    private $productUrl;

    public function __construct(int $shopId, string $categoryUrl, string $productUrl)
    {
        $this->shopId = $shopId;
        $this->categoryUrl = $categoryUrl;
        $this->productUrl = $productUrl;
    }

    public function getShopId(): int
    {
       return $this->shopId;
    }

    public function getCategoryUrl(): string
    {
       return $this->categoryUrl;
    }

    public function getProductUrl(): string
    {
       return $this->productUrl;
    }
}
