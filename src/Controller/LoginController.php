<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Shopify\Context;
use Shopify\Auth\OAuth;
use Shopify\Auth\OAuthCookie;
use Firebase\JWT\JWT;
use App\Entity\Shop;
use App\Repository\ShopRepository;
use App\Message\GetShopifyProducts;

class LoginController extends AbstractController
{
    use ShopifyTrait;

    #[Route('/login', name: 'app_login')]
    public function index(Request $request): Response
    {
        $this->shopifyInitialize();

        if ($request->isMethod('POST')) {
            $shop = $request->get('shop');
            $redirectPath = '/callback';
            $isOnline = $this->shopifySessionIsOnline();

            $response = new RedirectResponse($redirectPath);

            $url = OAuth::begin($shop, $redirectPath, $isOnline, function(OAuthCookie $cookie) use ($response) {
                $response->headers->setCookie(
                    Cookie::create(
                        $cookie->getName(),
                        $cookie->getValue(),
                        0,
                        '/',
                        null,
                        $cookie->isSecure(),
                        true,
                        false,
                        'None'
                    )
                );
                return true;
            });

            $response->setTargetUrl($url);

            return $response;
        }

        return $this->render('login/index.html.twig');
    }

    #[Route('/callback', name: 'app_callback')]
    public function callback(Request $request, ShopRepository $shopRepository, MessageBusInterface $bus): Response
    {
        $this->shopifyInitialize();

        // Shopify callback
        $cookies = $request->cookies->all();
        $query = $request->query->all();

        $url = $this->generateUrl('app_home');
        $response = new RedirectResponse($url);

        try {
            $session = OAuth::callback($cookies, $query, function(OAuthCookie $cookie) use ($response) {
                $response->headers->setCookie(
                    Cookie::create(
                        $cookie->getName(),
                        $cookie->getValue(),
                        0,
                        '/',
                        null,
                        $cookie->isSecure(),
                        true,
                        false,
                        'None'
                    )
                );
                return true;
            });
        } catch (\Exception $e) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $shopDomain = $session->getShop();
        $accessToken = $session->getAccessToken();
        $accessId = $session->getOnlineAccessInfo()->getId();

        $shop = $shopRepository->findOneBy(['domain' => $shopDomain]);
        if (empty($shop)) {
            $shop = new Shop();
            $shop->setCreatedAt(new \DateTimeImmutable());
            $flag = true;
        }

        $shop->setDomain($shopDomain);
        $shop->setAccessToken($accessToken);
        $shop->setUpdatedAt(new \DateTimeImmutable());

        $shopRepository->add($shop, true);

        $payload =[
            "dest" => $shopDomain,
            "sub" => $accessId
        ];
        $token = JWT::encode($payload, Context::$API_SECRET_KEY);

        $response->headers->setCookie(
            Cookie::create(
                'token',
                $token,
                0,
                '/',
                null,
                true,
                true,
                false,
                'None'
            )
        );

        if (isset($flag)) {
            $bus->dispatch(new GetShopifyProducts($shop->getId()));
        }

        return $response;
    }
}
