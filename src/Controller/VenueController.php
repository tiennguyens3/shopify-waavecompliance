<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;
use Shopify\Auth\FileSessionStorage;
use Shopify\Auth\OAuth;
use Shopify\Context;
use Shopify\Utils;
use App\Repository\ShopRepository;
use App\Repository\VenueRepository;
use App\Form\VenueType;
use App\Entity\Venue;

class VenueController extends AbstractController
{
    use ShopifyTrait;

    #[Route('/', name: 'app_home')]
    public function index(Request $request, ShopRepository $shopRepository, VenueRepository $venueRepository): Response
    {
        $this->shopifyInitialize();

        $cookies = $request->cookies->all();
        $isOnline = $this->shopifySessionIsOnline();

        $token = isset($cookies['token']) ? $cookies['token'] : '';
        $headers = ['Authorization' => "Bearer $token"];

        try {
            $session = Utils::loadCurrentSession($headers, $cookies, $isOnline);
        } catch(\Exception $e) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $shopDomain = $session->getShop();
        $shop = $shopRepository->findOneBy(['domain' => $shopDomain]);
        if (empty($shop)) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $venue = $shop->getVenue();
        if (empty($venue)) {
            $venue = new Venue();
            $venue->setCreatedAt(new \DateTimeImmutable());
        }

        $venue->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(VenueType::class, $venue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $venue->setShop($shop);
            $venueRepository->add($venue, true);

            // Call WAAVE API to update merchant compliance
            $categoryUrl = $this->generateUrl('app_category_index', ['shopId' => $shop->getId()], 0);
            $productUrl = $this->generateUrl('app_product_index', ['shopId' => $shop->getId()], 0);
            $pingUrl = $this->generateUrl('app_home_ping', [], 0);

            $client = new Client();
            $options = [
                'json' => [
                    'venue_id' => $venue->getVenueId(),
                    'password' => $venue->getPassword(),
                    'version' => '1.0.0',
                    'ping_url' => $pingUrl,
                    'category_url' => $categoryUrl,
                    'menu_item_url' => $productUrl
                ]
            ];

            try {
                $client->post($_ENV['WAAVE_API_URL'], $options);
            } catch(\Exception $e) {
                return $this->json(['message' => 'Venue ID does not match!']);
            }

            return $this->json([]);
        }

        return $this->render('venue/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/ping', name: 'app_home_ping')]
    public function show()
    {
        return $this->json([
            'version' => '1.0.0'
        ]);
    }
}
