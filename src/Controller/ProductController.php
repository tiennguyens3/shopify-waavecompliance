<?php

namespace App\Controller;

use App\Repository\ShopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
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
