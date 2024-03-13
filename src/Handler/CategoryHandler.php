<?php

namespace App\Handler;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ShopRepository;
use Shopify\Webhooks\Handler;
use Psr\Log\LoggerInterface;

class CategoryHandler implements Handler
{
    private $shopRepository;

    private $categoryRepository;

    private $logger;

    public function __construct(ShopRepository $shopRepository, CategoryRepository $categoryRepository, LoggerInterface $logger)
    {
        $this->shopRepository = $shopRepository;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    public function handle(string $topic, string $shop, array $requestBody): void
    {
        $this->logger->info("Handle category with body:", $requestBody);

        $shop = $this->shopRepository->findOneByDomain($shop);
        if (empty($shop)) {
            return;
        }

        if (empty($requestBody['id'])) {
            return;
        }

        $shopifyId = $requestBody['id'];
        $category = $this->categoryRepository->findOneBy([
            'shop' => $shop,
            'shopify_id' => $shopifyId
        ]);

        $date = new \DateTimeImmutable($requestBody['updated_at']);

        if (empty($category)) {
            $category = new Category();
            $category->setShop($shop);
            $category->setCreatedAt($date);
        }

        $category->setShopifyId($shopifyId);
        $category->setName($requestBody['title']);
        $category->setUpdatedAt($date);

        $this->categoryRepository->add($category, true);
    }
}