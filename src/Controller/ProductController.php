<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ShopRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Shopify\Clients\Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    use ShopifyTrait;

    #[Route('/shopify', name: 'app_product_shopify')]
    public function new(Request $request, ProductRepository $productRepository, ShopRepository $shopRepository, CategoryRepository $categoryRepository): JsonResponse
    {
        $shopId = $request->get('shop_id');
        $shop = $shopRepository->find($shopId);
        if (empty($shop)) {
            return $this->json([]);
        }

        $content = $request->getContent();
        $data = json_decode($content, true);
        if (empty($data['id'])) {
            return $this->json([]);
        }

        $shopifyId = $data['id'];
        $product = $productRepository->findOneBy([
            'shopify_id' => $shopifyId
        ]);

        $date = new \DateTimeImmutable($data['updated_at']);
        $price = 0;
        if (isset($data['variants'][0]['price'])) {
            $price = $data['variants'][0]['price'];
        }

        if (empty($product)) {
            $product = new Product();
            $product->setCreatedAt($date);
        }

        $product->setShop($shop);
        $product->setShopifyId($shopifyId);
        $product->setName($data['title']);
        $product->setPrice($price);
        $product->setUrl($data['handle']);
        $product->setUpdatedAt($date);

        // Get categories of this product

        $this->shopifyInitialize();
        $client = new Rest($shop->getDomain(), $shop->getAccessToken());

        // Custom collections
        $query = [
            'product_id' => $shopifyId
        ];
        $response = $client->get('custom_collections', [], $query);

        $body = $response->getDecodedBody();
        if ($body) {
            $collections = $body['custom_collections'];
            foreach ($collections as $value) {
                $category = $categoryRepository->findOneBy(['shopify_id' => $value['id']]);
                if (empty($category)) {
                    continue;
                }

                $product->addCategory($category);
            }
        }

        // Smart collections
        $query = [
            'product_id' => $shopifyId
        ];
        $response = $client->get('smart_collections', [], $query);

        $body = $response->getDecodedBody();
        if ($body) {
            $collections = $body['smart_collections'];
            foreach ($collections as $value) {
                $category = $categoryRepository->findOneBy(['shopify_id' => $value['id']]);
                if (empty($category)) {
                    continue;
                }

                $product->addCategory($category);
            }
        }

        $productRepository->add($product, true);

        return $this->json([
            'success' => true
        ]);
    }

    #[Route('/{shopId}')]
    public function index(Request $request, $shopId, ShopRepository $shopRepository)
    {
        $shop = $shopRepository->find($shopId);
        if (empty($shop)) {
            return $this->json([]);
        }

        $data = [];
        $products = $shop->getProducts();

        $after = $request->get('after');
        if ($after) {
            $date = new \DateTimeImmutable($after);
            $products = $products->filter(function($product) use ($date) {
                return $product->getUpdatedAt() >= $date;
            });
        }

        foreach ($products as $value) {
            $categories = [];
            foreach ($value->getCategories() as $category) {
                $categories[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName()
                ];
            }

            $data[] = [
                'id' => $value->getId(),
                'name' => $value->getName(),
                'sku' => $value->getSku(),
                'price' => $value->getPrice(),
                'permalink' => $this->buildProductUrl($shop, $value->getUrl()),
                'categories' => $categories
            ];
        }

        return $this->json($data);
    }

    private function buildProductUrl($shop, $url)
    {
        $domain = trim($shop->getDomain(), '/');

        return 'https://' . $domain . '/products/' . trim($url, '/');
    }
}
