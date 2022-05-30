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

        $this->getCustomCollections($client);
        $this->getSmartCollections($client);

        $this->entityManager->flush();
    }

    private function getCustomCollections($client)
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
            $this->newCategory($value, $client);
        }
    }

    private function getSmartCollections($client)
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
            $this->newCategory($value, $client);
        }
    }

    private function newCategory($data, $client)
    {
        $category = new Category();
        $category->setShopifyId($data['id']);
        $category->setName($data['title']);
        $category->setCreatedAt(new \DateTimeImmutable());

        $this->addProducts($category, $client);
        $this->entityManager->persist($category);
    }

    private function addProducts($category, $client)
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

            $product = new Product();
            $product->setShopifyId($value['id']);
            $product->setName($value['title']);
            $product->setPrice($price);
            $product->setUrl($value['handle']);
            $product->setCreatedAt(new \DateTimeImmutable());
            $product->addCategory($category);
            $category->addProduct($product);
        }
    }
}
