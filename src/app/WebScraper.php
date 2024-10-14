<?php

namespace App;

require __DIR__ . '/../../vendor/autoload.php';


use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WebScraper
{
    private \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient;
    private array $products = [];

    public function __construct() {

        $this->httpClient = HttpClient::create();
//        set_time_limit(60);
    }

    public function scrapeSite($url): array
    {
        try {

            $response = $this->httpClient->request('GET', $url);

            $html = $response->getContent();

            $crawler = new Crawler($html);

            $categoryLinks = $crawler->filter(
                'li.filter__title-item.eshop_category div.filter-wrapper-desktop a.filter__link'
                        )->each(function (Crawler $node){
                $categoryProducts = $this->getCategoryProducts(
                    $node->attr('href')
                );
                $this->products = array_merge($this->products, $categoryProducts);
                return [
                    'name' => trim($node->text()),
                    'product_count' => count($categoryProducts),
                ];
            });

            usort($categoryLinks, function ($a, $b) {
                return $b['product_count'] <=> $a['product_count'];
            });

            return [
                'categories' => $categoryLinks,
                'products' => $this->products
            ];
        }
        catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        } catch (TransportExceptionInterface $e) {
            return ['error' => $e->getMessage()];
        }

    }

    private function getCategoryProducts($baseUrl): array
    {
        $page = 1;
        $maxPage = 3;

        $allProducts = [];

        while (true) {
            $url = $baseUrl . '&p=' . $page;
//            echo "Kraabin lehekülge: $url\n";

            $response = $this->httpClient->request(
                'GET', $url, [
                    'headers' => [
                        'Accept' => 'text/html',
                    ],
                ]
            );

            if ($response->getStatusCode() !== 200) {
                break;
            }
            $htmlContent = $response->getContent();
            $crawler = new Crawler($htmlContent);

            $products = $crawler->filter('.products-grid__info')->each(function (Crawler $node) {
                $price = $node->filter('div.price-box span.price')->text();
                $price = str_replace(',', '.', $price);
                $price = str_replace(" €", "", $price);
                $priceNum = floatval($price);
                return [
                    'name' => $node->filter('.products-grid__title')->text(),
                    'price' => $priceNum
                ];
            });

            $allProducts = array_merge($allProducts, $products);

            $nextButton = $crawler->filter('.pagination__link.pagination__link--next');
            if ($nextButton->count() === 0 || $maxPage === $page) {
//                echo "Enam järgmist lehte ei leidu. Lõpetan kaapimise.\n";
                break;
            }

            $page++;
        }

        return $allProducts;
    }
}