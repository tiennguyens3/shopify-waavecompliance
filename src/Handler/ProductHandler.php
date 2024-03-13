<?php

namespace App\Handler;

use App\Entity\Product;
use App\Handler\ShopifyTrait;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ShopRepository;
use Shopify\Clients\Rest;
use Shopify\Webhooks\Handler;
use Psr\Log\LoggerInterface;

class ProductHandler implements Handler
{
    use ShopifyTrait;

    private $shopRepository;

    private $categoryRepository;

    private $productRepository;

    private $logger;

    public function __construct(ShopRepository $shopRepository, CategoryRepository $categoryRepository, ProductRepository $productRepository, LoggerInterface $logger)
    {
        $this->shopRepository = $shopRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function handle(string $topic, string $domain, array $requestBody): void
    {
        $this->logger->info("Handle product with body:", $requestBody);

        $shop = $this->shopRepository->findOneByDomain($domain);
        if (empty($shop)) {
            return;
        }

        if (empty($requestBody['id'])) {
            return;
        }

        $shopifyId = $requestBody['id'];
        $product = $this->productRepository->findOneBy([
            'shop' => $shop,
            'shopify_id' => $shopifyId
        ]);

        $date = new \DateTimeImmutable($requestBody['updated_at']);
        $price = 0;
        if (isset($requestBody['variants'][0]['price'])) {
            $price = $requestBody['variants'][0]['price'];
        }

        if (empty($product)) {
            $product = new Product();
            $product->setCreatedAt($date);
        }

        $product->setShop($shop);
        $product->setShopifyId($shopifyId);
        $product->setName($requestBody['title']);
        $product->setPrice($price);
        $product->setUrl($requestBody['handle']);
        $product->setUpdatedAt($date);

        $this->shopifyInitialize();
        $client = new Rest($shop->getDomain(), $shop->getAccessToken());

        $collections = [];

        // Custom collections
        $query = [
            'product_id' => $shopifyId
        ];
        $response = $client->get('custom_collections', [], $query);

        $body = $response->getDecodedBody();
        if (isset($body['custom_collections'])) {
            $collections = array_merge($collections, $body['custom_collections']);
        }

        // Smart collections
        $response = $client->get('smart_collections', [], $query);
        $body = $response->getDecodedBody();
        if (isset($body['smart_collections'])) {
            $collections = array_merge($collections, $body['smart_collections']);
        }

        foreach ($collections as $value) {
            $category = $this->categoryRepository->findOneBy([
                'shop' => $shop,
                'shopify_id' => $value['id']
            ]);
            if (empty($category)) {
                continue;
            }

            $product->addCategory($category);
        }

        $this->productRepository->add($product, true);
    }
}