<?php

namespace App\Catalog\UI\Controller;

use App\Catalog\Application\ProductCatalogFinder;
use App\Catalog\Application\ProductPriceResolver;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\Repository\ProductSearchCriteria;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Back-office catalogue page (staff-facing, Twig). The self-service React
 * front (EPIC 13) will browse the same data through the JSON API (EPIC 9)
 * instead of this controller.
 */
class CatalogController extends AbstractController
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly ProductCatalogFinder $catalogFinder,
        private readonly ProductPriceResolver $priceResolver,
        private readonly CompanyRepository $companies,
    ) {
    }

    #[Route('/catalogue', name: 'app_catalog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $searchTerm = $request->query->getString('q') ?: null;
        $category = ProductCategory::tryFrom((string) $request->query->get('category', ''));
        $page = max(1, $request->query->getInt('page', 1));
        $companyId = $request->query->get('company');
        $company = $companyId ? $this->companies->find($companyId) : null;

        $catalogPage = $this->catalogFinder->search(ProductSearchCriteria::create(
            searchTerm: $searchTerm,
            category: $category,
            page: $page,
            perPage: self::PER_PAGE,
        ));

        $unitPrices = [];
        if (null !== $company) {
            foreach ($catalogPage->items as $product) {
                $unitPrices[$product->getId()] = $this->priceResolver->resolveUnitPrice($product->getId(), 1, $company);
            }
        }

        return $this->render('catalog/index.html.twig', [
            'catalogPage' => $catalogPage,
            'categories' => ProductCategory::cases(),
            'companies' => $this->companies->findAll(),
            'searchTerm' => $searchTerm,
            'selectedCategory' => $category,
            'selectedCompany' => $company,
            'unitPrices' => $unitPrices,
        ]);
    }
}
