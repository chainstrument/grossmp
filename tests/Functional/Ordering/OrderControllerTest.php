<?php

namespace App\Tests\Functional\Ordering;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Identity\Domain\Entity\Company;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\ValueObject\Address;
use App\Ordering\Application\CartManager;
use App\Ordering\Application\OrderWorkflow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OrderControllerTest extends WebTestCase
{
    private const TEST_SKU = 'ORDER-CTRL-TEST-1';
    private const SIRET_A = '22233344455566';
    private const SIRET_B = '33344455566677';
    private const BUYER_A_EMAIL = 'order-ctrl-buyer-a@grossmp.local';
    private const BUYER_B_EMAIL = 'order-ctrl-buyer-b@grossmp.local';
    private const STAFF_EMAIL = 'order-ctrl-staff@grossmp.local';

    private EntityManagerInterface $em;
    private Company $companyA;
    private User $buyerA;
    private User $buyerB;
    private User $staff;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        foreach ([self::SIRET_A, self::SIRET_B] as $siret) {
            $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\OrderStatusHistory h WHERE h.order IN (SELECT o FROM App\Ordering\Domain\Entity\Order o WHERE o.company IN (SELECT c FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret))')
                ->setParameter('siret', $siret)->execute();
            $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\OrderLine l WHERE l.order IN (SELECT o FROM App\Ordering\Domain\Entity\Order o WHERE o.company IN (SELECT c FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret))')
                ->setParameter('siret', $siret)->execute();
            $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\Order o WHERE o.company IN (SELECT c FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret)')
                ->setParameter('siret', $siret)->execute();
        }
        $this->em->createQuery('DELETE FROM App\Catalog\Domain\Entity\Product p WHERE p.reference = :ref')
            ->setParameter('ref', self::TEST_SKU)->execute();
        foreach ([self::BUYER_A_EMAIL, self::BUYER_B_EMAIL, self::STAFF_EMAIL] as $email) {
            $this->em->createQuery('DELETE FROM App\Identity\Domain\Entity\User u WHERE u.email = :email')
                ->setParameter('email', $email)->execute();
        }
        foreach ([self::SIRET_A, self::SIRET_B] as $siret) {
            $this->em->createQuery('DELETE FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret')
                ->setParameter('siret', $siret)->execute();
        }

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $this->companyA = new Company();
        $this->companyA->setName('Order Ctrl Company A');
        $this->companyA->setSiret(self::SIRET_A);
        $this->companyA->setBillingAddress($this->address());
        $this->companyA->setShippingAddress($this->address());
        $this->em->persist($this->companyA);

        $companyB = new Company();
        $companyB->setName('Order Ctrl Company B');
        $companyB->setSiret(self::SIRET_B);
        $companyB->setBillingAddress($this->address());
        $companyB->setShippingAddress($this->address());
        $this->em->persist($companyB);

        $this->product = new Product(new Sku(self::TEST_SKU), 'Produit order controller', ProductCategory::FOOD, Money::fromEuros(10));
        $this->product->adjustStock(50);
        $this->em->persist($this->product);

        $this->buyerA = new User();
        $this->buyerA->setEmail(self::BUYER_A_EMAIL);
        $this->buyerA->setFirstName('Buyer');
        $this->buyerA->setLastName('A');
        $this->buyerA->setRoles([User::ROLE_BUYER]);
        $this->buyerA->setCompany($this->companyA);
        $this->buyerA->setPassword($hasher->hashPassword($this->buyerA, 'password123'));
        $this->em->persist($this->buyerA);

        $this->buyerB = new User();
        $this->buyerB->setEmail(self::BUYER_B_EMAIL);
        $this->buyerB->setFirstName('Buyer');
        $this->buyerB->setLastName('B');
        $this->buyerB->setRoles([User::ROLE_BUYER]);
        $this->buyerB->setCompany($companyB);
        $this->buyerB->setPassword($hasher->hashPassword($this->buyerB, 'password123'));
        $this->em->persist($this->buyerB);

        $this->staff = new User();
        $this->staff->setEmail(self::STAFF_EMAIL);
        $this->staff->setFirstName('Staff');
        $this->staff->setLastName('Test');
        $this->staff->setRoles([User::ROLE_VALIDATOR]);
        $this->staff->setPassword($hasher->hashPassword($this->staff, 'password123'));
        $this->em->persist($this->staff);

        $this->em->flush();

        self::ensureKernelShutdown();
    }

    private function submittedOrderForCompanyA(): int
    {
        self::bootKernel();
        $container = self::getContainer();
        $cartManager = $container->get(CartManager::class);
        $workflow = $container->get(OrderWorkflow::class);
        $em = $container->get(EntityManagerInterface::class);

        $company = $em->find(Company::class, $this->companyA->getId());
        $product = $em->find(Product::class, $this->product->getId());

        $cart = $cartManager->getOrCreateDraftCart($company);
        $cartManager->addProduct($cart, $product->getId(), 2);
        $workflow->apply($cart, 'submit');

        $orderId = $cart->getId();
        self::ensureKernelShutdown();

        return $orderId;
    }

    public function testSubmittingTheCartRedirectsToTheOrderTrackingPage(): void
    {
        $client = static::createClient();

        // Re-fetch through this (post-createClient) kernel's EntityManager —
        // entities from setUp()'s own boot cycle would be detached here.
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $buyer = $em->find(User::class, $this->buyerA->getId());
        $product = $em->find(Product::class, $this->product->getId());

        $cartManager = self::getContainer()->get(CartManager::class);
        $cart = $cartManager->getOrCreateDraftCart($buyer->getCompany(), $buyer);
        $cartManager->addProduct($cart, $product->getId(), 2);

        $client->loginUser($buyer);
        $crawler = $client->request('GET', '/panier/recapitulatif');
        $form = $crawler->selectButton('Valider la commande')->form();
        $client->submit($form);

        self::assertResponseRedirects();
        $crawler = $client->followRedirect();

        self::assertSelectorTextContains('[data-testid="order-status"]', 'Soumise');
        self::assertCount(1, $crawler->filter('[data-testid="history-row"]'));
    }

    public function testStaffSeesValidateButtonBuyerDoesNot(): void
    {
        $orderId = $this->submittedOrderForCompanyA();

        $client = static::createClient();
        $client->loginUser($this->staff);
        $client->request('GET', '/commandes/'.$orderId);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('button:contains("Valider")');

        // Same client, re-authenticated as a different user — createClient()
        // can only be called once per test.
        $client->loginUser($this->buyerA);
        $client->request('GET', '/commandes/'.$orderId);

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('button:contains("Valider")');
        self::assertSelectorExists('button:contains("Annuler")');
    }

    public function testBuyerCannotTriggerAStaffOnlyTransition(): void
    {
        $orderId = $this->submittedOrderForCompanyA();

        $client = static::createClient();
        $client->loginUser($this->buyerA);

        $token = $this->orderTransitionToken($client, $orderId);
        $client->request('POST', '/commandes/'.$orderId.'/transition/validate', ['_token' => $token]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testStaffCanValidateAnOrder(): void
    {
        $orderId = $this->submittedOrderForCompanyA();

        $client = static::createClient();
        $client->loginUser($this->staff);

        $token = $this->orderTransitionToken($client, $orderId);
        $client->request('POST', '/commandes/'.$orderId.'/transition/validate', ['_token' => $token]);

        self::assertResponseRedirects('/commandes/'.$orderId);
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="order-status"]', 'Validée');
    }

    /**
     * The CSRF token is stateless/Origin-based (Symfony 7.3+) — it can only
     * be read off an actually-rendered form, not generated ahead of time
     * from the token manager service directly.
     */
    private function orderTransitionToken(KernelBrowser $client, int $orderId): string
    {
        $crawler = $client->request('GET', '/commandes/'.$orderId);

        return $crawler->filter('form input[name="_token"]')->first()->attr('value');
    }

    public function testAnotherCompanysOrderIsNotVisible(): void
    {
        $orderId = $this->submittedOrderForCompanyA();

        $client = static::createClient();
        $client->loginUser($this->buyerB);

        $client->request('GET', '/commandes/'.$orderId);

        self::assertResponseStatusCodeSame(404);
    }

    public function testOrderIndexIsScopedToTheBuyersCompany(): void
    {
        $this->submittedOrderForCompanyA();

        $client = static::createClient();
        $client->loginUser($this->buyerB);
        $crawler = $client->request('GET', '/commandes');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-testid="order-row"]'));
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
