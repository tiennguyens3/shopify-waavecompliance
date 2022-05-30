<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ShopRepository;
use Shopify\Clients\Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    use ShopifyTrait;

    #[Route('/shopify', name: 'app_category_shopify', methods: ['POST'])]
    public function new(Request $request, CategoryRepository $categoryRepository, ShopRepository $shopRepository): JsonResponse
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
        $category = $categoryRepository->findOneBy([
            'shopify_id' => $shopifyId
        ]);

        $date = new \DateTimeImmutable($data['updated_at']);

        if (empty($category)) {
            $category = new Category();
            $category->setShopId($shopId);
            $category->setCreatedAt($date);
        }

        $category->setShopifyId($shopifyId);
        $category->setName($data['title']);
        $category->setUpdatedAt($date);

        $categoryRepository->add($category, true);

        // Update product category
        $this->shopifyInitialize();
        $client = new Rest($shop->getDomain(), $shop->getAccessToken());
        $query = [
            'limit' => 250,
            'collection_id' => $category->getShopifyId()
        ];
        $response = $client->get('products', [], $query);

        $body = $response->getDecodedBody();
        if (empty($body)) {
            return $this->json([]);
        }

        $products = $body['products'];

        foreach ($products as $value) {
            $price = 0;
            if (isset($value['variants'][0]['price'])) {
                $price = $value['variants'][0]['price'];
            }

            $shopifyId = $value['id'];
            $product = $productRepository->findOneBy([
                'shopify_id' => $shopifyId
            ]);

            if (empty($product)) {
                $product = new Product();
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

        $categoryRepository->add($category, true);

        return $this->json([
            'success' => true
        ]);
    }
}
