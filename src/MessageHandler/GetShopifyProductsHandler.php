<?php

namespace App\MessageHandler;

use App\Entity\Shop;
use App\Entity\Product;
use App\Entity\Category;
use App\Message\GetShopifyProducts;
use Shopify\Clients\Rest;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class GetShopifyProductsHandler implements MessageHandlerInterface
{
    private $entityManager;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->entityManager = $doctrine->getManager();
    }

    public function __invoke(GetShopifyProducts $message)
    {
        $shopId = $message->getShopId();

        $shop = $this->entityManager->getRepository(Shop::class)->find($shopId);

        if (empty($shop)) {
            return;
        }

        $client = new Rest($shop->getDomain(), $shop->getAccessToken());

        $this->getCustomCollections($shop, $client);
        $this->getSmartCollections($shop, $client);

        $this->entityManager->flush();
    }

    private function getCustomCollections($shop, $client)
    {
        $query = [
            'limit' => 250
        ];
        $response = $client->get('custom_collections', [], $query);

        $body = $response->getDecodedBody();

        if (empty($body)) {
            return;
        }

        $collections = $body['custom_collections'];

        foreach ($collections as $value) {
            $this->newCategory($value, $shop, $client);
        }
    }

    private function getSmartCollections($shop, $client)
    {
        $query = [
            'limit' => 250
        ];
        $response = $client->get('smart_collections', [], $query);

        $body = $response->getDecodedBody();

        if (empty($body)) {
            return;
        }

        $collections = $body['smart_collections'];

        foreach ($collections as $value) {
            $this->newCategory($value, $shop, $client);
        }
    }

    private function newCategory($data, $shop, $client)
    {
        $category = new Category();
        $category->setShopId($shop->getId());
        $category->setShopifyId($data['id']);
        $category->setName($data['title']);
        $category->setCreatedAt(new \DateTimeImmutable());

        $this->addProducts($category, $shop, $client);
        $this->entityManager->persist($category);
    }

    private function addProducts($category, $shop, $client)
    {
        $query = [
            'limit' => 250,
            'collection_id' => $category->getShopifyId()
        ];
        $response = $client->get('products', [], $query);

        $body = $response->getDecodedBody();
        if (empty($body)) {
            return;
        }

        $products = $body['products'];

        foreach ($products as $value) {
            $price = 0;
            if (isset($value['variants'][0]['price'])) {
                $price = $value['variants'][0]['price'];
            }

            $shopifyId = $value['id'];
            $product = $this->entityManager->findOneBy([
                'shopify_id' => $shopifyId
            ]);

            if (empty($product)) {
                $product = new Product();
                $product->setShopId($shop->getId());
                $product->setCreatedAt(new \DateTimeImmutable());
            }

            $product->setShopifyId($shopifyId);
            $product->setName($value['title']);
            $product->setPrice($price);
            $product->setUrl($value['handle']);
            $product->setUpdatedAt(new \DateTimeImmutable());

            $product->addCategory($category);
            $category->addProduct($product);
        }
    }
}
