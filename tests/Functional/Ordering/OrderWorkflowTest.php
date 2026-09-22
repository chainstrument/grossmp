<?php

namespace App\Tests\Functional\Ordering;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Identity\Domain\Entity\Company;
use App\Identity\Domain\ValueObject\Address;
use App\Ordering\Application\CartManager;
use App\Ordering\Application\OrderWorkflow;
use App\Ordering\Domain\Enum\OrderStatus;
use App\Ordering\Domain\Repository\OrderStatusHistoryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Exception\LogicException as WorkflowLogicException;

class OrderWorkflowTest extends KernelTestCase
{
    private const TEST_SKU = 'WORKFLOW-TEST-1';
    private const TEST_SIRET = '11122233344455';

    private EntityManagerInterface $em;
    private OrderWorkflow $orderWorkflow;
    private CartManager $cartManager;
    private OrderStatusHistoryRepositoryInterface $history;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->orderWorkflow = $container->get(OrderWorkflow::class);
        $this->cartManager = $container->get(CartManager::class);
        $this->history = $container->get(OrderStatusHistoryRepositoryInterface::class);

        // Same-file SQLite persists across runs — clean up leftovers first.
        $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\OrderStatusHistory h WHERE h.order IN (SELECT o FROM App\Ordering\Domain\Entity\Order o WHERE o.company IN (SELECT c FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret))')
            ->setParameter('siret', self::TEST_SIRET)->execute();
        $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\OrderLine l WHERE l.product IN (SELECT p FROM App\Catalog\Domain\Entity\Product p WHERE p.reference = :ref)')
            ->setParameter('ref', self::TEST_SKU)->execute();
        $this->em->createQuery('DELETE FROM App\Ordering\Domain\Entity\Order o WHERE o.company IN (SELECT c FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret)')
            ->setParameter('siret', self::TEST_SIRET)->execute();
        $this->em->createQuery('DELETE FROM App\Catalog\Domain\Entity\Product p WHERE p.reference = :ref')
            ->setParameter('ref', self::TEST_SKU)->execute();
        $this->em->createQuery('DELETE FROM App\Identity\Domain\Entity\Company c WHERE c.siret = :siret')
            ->setParameter('siret', self::TEST_SIRET)->execute();
    }

    public function testFullHappyPathConsumesStockOnceAndRecordsHistory(): void
    {
        $company = $this->aCompany();
        $product = $this->aProduct(stock: 10);
        $this->em->persist($company);
        $this->em->persist($product);
        $this->em->flush();

        $cart = $this->cartManager->getOrCreateDraftCart($company);
        $this->cartManager->addProduct($cart, $product->getId(), 4);

        foreach (['submit', 'validate', 'prepare', 'ship', 'invoice'] as $transition) {
            self::assertTrue($this->orderWorkflow->can($cart, $transition), "should be able to $transition");
            $this->orderWorkflow->apply($cart, $transition);
        }

        self::assertSame(OrderStatus::INVOICED, $cart->getStatus());

        // Stock is consumed exactly once, at "prepare".
        $this->em->refresh($product);
        self::assertSame(6, $product->getStockQuantity());

        $history = $this->history->findByOrder($cart);
        self::assertCount(5, $history);
        self::assertSame(['submit', 'validate', 'prepare', 'ship', 'invoice'], array_map(
            fn ($entry) => $entry->getTransition(),
            $history
        ));
        self::assertSame(OrderStatus::DRAFT, $history[0]->getFromStatus());
        self::assertSame(OrderStatus::SUBMITTED, $history[0]->getToStatus());
    }

    public function testCannotSubmitAnEmptyCart(): void
    {
        $company = $this->aCompany();
        $this->em->persist($company);
        $this->em->flush();

        $cart = $this->cartManager->getOrCreateDraftCart($company);

        self::assertFalse($this->orderWorkflow->can($cart, 'submit'));

        $this->expectException(WorkflowLogicException::class);
        $this->orderWorkflow->apply($cart, 'submit');
    }

    public function testCannotPrepareWhenStockDroppedBelowWhatWasOrdered(): void
    {
        $company = $this->aCompany();
        $product = $this->aProduct(stock: 10);
        $this->em->persist($company);
        $this->em->persist($product);
        $this->em->flush();

        $cart = $this->cartManager->getOrCreateDraftCart($company);
        $this->cartManager->addProduct($cart, $product->getId(), 8);

        $this->orderWorkflow->apply($cart, 'submit');
        $this->orderWorkflow->apply($cart, 'validate');

        // Simulate stock consumed elsewhere between validation and preparation.
        $product->adjustStock(-5);
        $this->em->flush();

        self::assertFalse($this->orderWorkflow->can($cart, 'prepare'));

        try {
            $this->orderWorkflow->apply($cart, 'prepare');
            self::fail('Expected a WorkflowLogicException.');
        } catch (WorkflowLogicException) {
            // expected
        }

        self::assertSame(OrderStatus::VALIDATED, $cart->getStatus());
    }

    #[DataProvider('cancellableStatuses')]
    public function testCancelIsAllowedFromDraftSubmittedOrValidated(string $reachVia): void
    {
        $company = $this->aCompany();
        $product = $this->aProduct(stock: 10);
        $this->em->persist($company);
        $this->em->persist($product);
        $this->em->flush();

        $cart = $this->cartManager->getOrCreateDraftCart($company);
        $this->cartManager->addProduct($cart, $product->getId(), 1);

        foreach (explode(',', $reachVia) as $transition) {
            if ('' === $transition) {
                continue;
            }
            $this->orderWorkflow->apply($cart, $transition);
        }

        self::assertTrue($this->orderWorkflow->can($cart, 'cancel'));
        $this->orderWorkflow->apply($cart, 'cancel');
        self::assertSame(OrderStatus::CANCELLED, $cart->getStatus());
    }

    public static function cancellableStatuses(): iterable
    {
        yield 'from draft' => [''];
        yield 'from submitted' => ['submit'];
        yield 'from validated' => ['submit,validate'];
    }

    public function testCancelIsNotAllowedOncePreparing(): void
    {
        $company = $this->aCompany();
        $product = $this->aProduct(stock: 10);
        $this->em->persist($company);
        $this->em->persist($product);
        $this->em->flush();

        $cart = $this->cartManager->getOrCreateDraftCart($company);
        $this->cartManager->addProduct($cart, $product->getId(), 1);
        $this->orderWorkflow->apply($cart, 'submit');
        $this->orderWorkflow->apply($cart, 'validate');
        $this->orderWorkflow->apply($cart, 'prepare');

        self::assertFalse($this->orderWorkflow->can($cart, 'cancel'));
    }

    private function aCompany(): Company
    {
        $company = new Company();
        $company->setName('Test Workflow Company');
        $company->setSiret(self::TEST_SIRET);
        $address = new Address();
        $address->setStreet('1 rue du Test');
        $address->setPostalCode('75000');
        $address->setCity('Paris');
        $address->setCountry('FR');
        $company->setBillingAddress($address);
        $company->setShippingAddress($address);

        return $company;
    }

    private function aProduct(int $stock): Product
    {
        $product = new Product(new Sku(self::TEST_SKU), 'Produit workflow', ProductCategory::FOOD, Money::fromEuros(10));
        $product->adjustStock($stock);

        return $product;
    }
}
