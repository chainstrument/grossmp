<?php

namespace App\Tests\Functional\Catalog;

use App\Catalog\Domain\Entity\PriceTier;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Identity\Domain\Entity\Company;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\ValueObject\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CatalogControllerTest extends WebTestCase
{
    private const TEST_SKU = 'CATALOG-TEST-1';
    private const STAFF_EMAIL = 'catalog-test-staff@grossmp.local';
    private const BUYER_EMAIL = 'catalog-test-buyer@grossmp.local';
    private const TEST_SIRET = '12345678901234';

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // Same-file SQLite persists across runs (see LoginTest) — clean up leftovers first.
        $this->em->createQuery('DELETE FROM App\Catalog\Domain\Entity\PriceTier t WHERE t.product IN (SELECT p FROM App\Catalog\Domain\Entity\Product p WHERE p.reference = :ref)')
            ->setParameter('ref', self::TEST_SKU)
            ->execute();
        $this->em->createQuery('DELETE FROM App\Catalog\Domain\Entity\Product p WHERE p.reference = :ref')
            ->setParameter('ref', self::TEST_SKU)
            ->execute();
        $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\OrderLine l WHERE l.product IN (SELECT p FROM App\Catalog\Domain\Entity\Product p WHERE p.reference = :ref)')
            ->setParameter('ref', self::TEST_SKU)
            ->execute();
        $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\Order o WHERE o.company IN (SELECT c FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret)')
            ->setParameter('siret', self::TEST_SIRET)
            ->execute();
        $this->em->createQuery('DELETE FROM App\Identity\Domain\Entity\User u WHERE u.email = :email')
            ->setParameter('email', self::STAFF_EMAIL)
            ->execute();
        $this->em->createQuery('DELETE FROM App\Identity\Domain\Entity\User u WHERE u.email = :email')
            ->setParameter('email', self::BUYER_EMAIL)
            ->execute();
        $this->em->createQuery('DELETE FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret')
            ->setParameter('siret', self::TEST_SIRET)
            ->execute();

        self::ensureKernelShutdown();
    }

    public function testCatalogPageShowsNegotiatedPriceForSelectedCompany(): void
    {
        $client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $company = new Company();
        $company->setName('Test Catalog Company');
        $company->setSiret(self::TEST_SIRET);
        $company->setBillingAddress($this->address());
        $company->setShippingAddress($this->address());
        $this->em->persist($company);

        $product = new Product(new Sku(self::TEST_SKU), 'Produit de test catalogue', ProductCategory::FOOD, Money::fromEuros(10));
        $product->adjustStock(100);
        $product->addPriceTier(new PriceTier(1, Money::fromEuros(6), $company));
        $this->em->persist($product);

        $staff = new User();
        $staff->setEmail(self::STAFF_EMAIL);
        $staff->setFirstName('Staff');
        $staff->setLastName('Test');
        $staff->setRoles([User::ROLE_VALIDATOR]);
        $staff->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($staff, 'password123'));
        $this->em->persist($staff);

        $this->em->flush();

        $client->loginUser($staff);
        $crawler = $client->request('GET', '/catalogue?company='.$company->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Produit de test catalogue');
        self::assertSelectorTextContains('table', '6,00');
    }

    public function testCatalogPageWithoutCompanyShowsBasePrice(): void
    {
        $client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $product = new Product(new Sku(self::TEST_SKU), 'Produit de test catalogue', ProductCategory::FOOD, Money::fromEuros(10));
        $product->adjustStock(100);
        $this->em->persist($product);

        $staff = new User();
        $staff->setEmail(self::STAFF_EMAIL);
        $staff->setFirstName('Staff');
        $staff->setLastName('Test');
        $staff->setRoles([User::ROLE_VALIDATOR]);
        $staff->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($staff, 'password123'));
        $this->em->persist($staff);

        $this->em->flush();

        $client->loginUser($staff);
        $client->request('GET', '/catalogue?q=catalogue');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', '10,00');
    }

    public function testStaffWithoutCompanyDoesNotSeeAnAddToCartButton(): void
    {
        $client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $product = new Product(new Sku(self::TEST_SKU), 'Produit de test catalogue', ProductCategory::FOOD, Money::fromEuros(10));
        $product->adjustStock(100);
        $this->em->persist($product);

        $staff = new User();
        $staff->setEmail(self::STAFF_EMAIL);
        $staff->setFirstName('Staff');
        $staff->setLastName('Test');
        $staff->setRoles([User::ROLE_VALIDATOR]);
        $staff->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($staff, 'password123'));
        $this->em->persist($staff);

        $this->em->flush();

        $client->loginUser($staff);
        $crawler = $client->request('GET', '/catalogue?q=catalogue');

        self::assertResponseIsSuccessful();
        // The only <form> elements inside the table are the per-row "add to cart" ones.
        self::assertCount(0, $crawler->filter('table form'));
    }

    public function testBuyerCanAddAProductToTheCartDirectlyFromTheCatalog(): void
    {
        $client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $company = new Company();
        $company->setName('Test Catalog Buyer Company');
        $company->setSiret(self::TEST_SIRET);
        $company->setBillingAddress($this->address());
        $company->setShippingAddress($this->address());
        $this->em->persist($company);

        $product = new Product(new Sku(self::TEST_SKU), 'Produit de test catalogue', ProductCategory::FOOD, Money::fromEuros(10));
        $product->adjustStock(100);
        $this->em->persist($product);

        $buyer = new User();
        $buyer->setEmail(self::BUYER_EMAIL);
        $buyer->setFirstName('Buyer');
        $buyer->setLastName('Test');
        $buyer->setRoles([User::ROLE_BUYER]);
        $buyer->setCompany($company);
        $buyer->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($buyer, 'password123'));
        $this->em->persist($buyer);

        $this->em->flush();

        $client->loginUser($buyer);
        $crawler = $client->request('GET', '/catalogue?q=catalogue');

        self::assertResponseIsSuccessful();
        $addForms = $crawler->filter('table form');
        self::assertCount(1, $addForms);

        $form = $addForms->form(['add_to_cart[quantity]' => '3']);
        $client->submit($form);

        self::assertResponseRedirects('/panier');
        $crawler = $client->followRedirect();

        self::assertSelectorTextContains('[data-testid="flash-success"]', 'ajouté');
        self::assertCount(1, $crawler->filter('[data-testid="cart-line"]'));
        // 3 units at 10€ = 30,00 €
        self::assertSelectorTextContains('[data-testid="cart-total"]', '30,00');
    }

    public function testCatalogPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/catalogue');

        self::assertResponseRedirects('/login');
    }

    private function address(): Address
    {
        $address = new Address();
        $address->setStreet('1 rue du Test');
        $address->setPostalCode('75000');
        $address->setCity('Paris');
        $address->setCountry('FR');

        return $address;
    }
}
