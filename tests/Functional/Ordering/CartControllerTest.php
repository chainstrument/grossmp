<?php

namespace App\Tests\Functional\Ordering;

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

class CartControllerTest extends WebTestCase
{
    private const TEST_SKU = 'CART-TEST-1';
    private const BUYER_EMAIL = 'cart-test-buyer@grossmp.local';
    private const TEST_SIRET = '98765432109876';

    private EntityManagerInterface $em;
    private Company $company;
    private Product $product;
    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // Same-file SQLite persists across runs — clean up leftovers first.
        $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\OrderLine l WHERE l.product IN (SELECT p FROM App\Catalog\Domain\Entity\Product p WHERE p.reference = :ref)')
            ->setParameter('ref', self::TEST_SKU)->execute();
        $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\Order o WHERE o.company IN (SELECT c FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret)')
            ->setParameter('siret', self::TEST_SIRET)->execute();
        $this->em->createQuery('DELETE FROM App\Catalog\Domain\Entity\Product p WHERE p.reference = :ref')
            ->setParameter('ref', self::TEST_SKU)->execute();
        $this->em->createQuery('DELETE FROM App\Identity\Domain\Entity\User u WHERE u.email = :email')
            ->setParameter('email', self::BUYER_EMAIL)->execute();
        $this->em->createQuery('DELETE FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret')
            ->setParameter('siret', self::TEST_SIRET)->execute();

        $this->company = new Company();
        $this->company->setName('Test Cart Company');
        $this->company->setSiret(self::TEST_SIRET);
        $address = new Address();
        $address->setStreet('1 rue du Test');
        $address->setPostalCode('75000');
        $address->setCity('Paris');
        $address->setCountry('FR');
        $this->company->setBillingAddress($address);
        $this->company->setShippingAddress($address);
        $this->em->persist($this->company);

        $this->product = new Product(new Sku(self::TEST_SKU), 'Produit panier de test', ProductCategory::FOOD, Money::fromEuros(10));
        $this->product->adjustStock(5);
        $this->em->persist($this->product);

        $this->buyer = new User();
        $this->buyer->setEmail(self::BUYER_EMAIL);
        $this->buyer->setFirstName('Buyer');
        $this->buyer->setLastName('Test');
        $this->buyer->setRoles([User::ROLE_BUYER]);
        $this->buyer->setCompany($this->company);
        $this->buyer->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($this->buyer, 'password123'));
        $this->em->persist($this->buyer);

        $this->em->flush();

        self::ensureKernelShutdown();
    }

    public function testAddingAProductCreatesALineAndUpdatesTheTotal(): void
    {
        $client = static::createClient();
        $client->loginUser($this->buyer);

        $crawler = $client->request('GET', '/panier');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'add_to_cart[product]' => (string) $this->product->getId(),
            'add_to_cart[quantity]' => '2',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/panier');
        $crawler = $client->followRedirect();

        self::assertSelectorTextContains('[data-testid="flash-success"]', 'ajouté');
        self::assertCount(1, $crawler->filter('[data-testid="cart-line"]'));
        // 2 units at 10€ = 20,00 €
        self::assertSelectorTextContains('[data-testid="cart-total"]', '20,00');
    }

    public function testAddingMoreThanAvailableStockIsRejected(): void
    {
        $client = static::createClient();
        $client->loginUser($this->buyer);

        $crawler = $client->request('GET', '/panier');
        $form = $crawler->selectButton('Ajouter')->form([
            'add_to_cart[product]' => (string) $this->product->getId(),
            'add_to_cart[quantity]' => '99',
        ]);
        $client->submit($form);

        // Re-renders the form with a validation error (no redirect). Symfony
        // 7.3+ automatically responds 422 when render() is given an invalid form.
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'Stock insuffisant');
    }

    public function testRemovingALine(): void
    {
        $client = static::createClient();
        $client->loginUser($this->buyer);

        $crawler = $client->request('GET', '/panier');
        $form = $crawler->selectButton('Ajouter')->form([
            'add_to_cart[product]' => (string) $this->product->getId(),
            'add_to_cart[quantity]' => '1',
        ]);
        $client->submit($form);
        $crawler = $client->followRedirect();

        $removeForm = $crawler->filter('[data-testid="cart-line"] form')->last()->form();
        $client->submit($removeForm);
        $crawler = $client->followRedirect();

        self::assertCount(0, $crawler->filter('[data-testid="cart-line"]'));
    }

    public function testCartRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/panier');

        self::assertResponseRedirects('/login');
    }

    public function testUserWithoutCompanyIsRedirectedToTheCatalog(): void
    {
        $client = static::createClient();

        $staff = new User();
        $staff->setEmail('cart-test-staff-no-company@grossmp.local');
        $staff->setFirstName('Staff');
        $staff->setLastName('NoCompany');
        $staff->setRoles([User::ROLE_VALIDATOR]);
        $staff->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($staff, 'password123'));

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Identity\Domain\Entity\User u WHERE u.email = :email')
            ->setParameter('email', $staff->getEmail())->execute();
        $em->persist($staff);
        $em->flush();

        $client->loginUser($staff);
        $client->request('GET', '/panier');

        self::assertResponseRedirects('/catalogue');
    }
}
