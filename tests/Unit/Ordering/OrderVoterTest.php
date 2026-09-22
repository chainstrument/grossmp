<?php

namespace App\Tests\Unit\Ordering;

use App\Identity\Domain\Entity\Company;
use App\Identity\Domain\Entity\User;
use App\Ordering\Domain\Entity\Order;
use App\Ordering\Infrastructure\Security\OrderVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Pure unit test: no Symfony kernel, no database.
 */
class OrderVoterTest extends TestCase
{
    private OrderVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new OrderVoter();
    }

    public function testBuyerCanViewTheirOwnCompanysOrder(): void
    {
        $company = $this->aCompany(1);

        self::assertGranted($this->vote($this->aBuyer($company), OrderVoter::VIEW, new Order($company)));
    }

    public function testBuyerCannotViewAnotherCompanysOrder(): void
    {
        $buyer = $this->aBuyer($this->aCompany(1));

        self::assertDenied($this->vote($buyer, OrderVoter::VIEW, new Order($this->aCompany(2))));
    }

    public function testUserWithoutCompanyCannotViewAnOrder(): void
    {
        self::assertDenied($this->vote($this->aBuyer(null), OrderVoter::VIEW, new Order($this->aCompany(1))));
    }

    public function testValidatorCanViewAnyOrder(): void
    {
        self::assertGranted($this->vote($this->aValidator(), OrderVoter::VIEW, new Order($this->aCompany(1))));
    }

    public function testAnonymousTokenIsDenied(): void
    {
        $result = $this->voter->vote(new NullToken(), new Order($this->aCompany(1)), [OrderVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function staffOnlyTransitions(): iterable
    {
        yield 'validate' => ['validate'];
        yield 'prepare' => ['prepare'];
        yield 'ship' => ['ship'];
        yield 'invoice' => ['invoice'];
    }

    #[DataProvider('staffOnlyTransitions')]
    public function testOnlyAValidatorCanDriveTheFulfillmentPipeline(string $transition): void
    {
        $company = $this->aCompany(1);
        $order = new Order($company);
        $attribute = OrderVoter::attributeForTransition($transition);

        self::assertGranted($this->vote($this->aValidator(), $attribute, $order));
        self::assertDenied($this->vote($this->aBuyer($company), $attribute, $order));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function companyTransitions(): iterable
    {
        yield 'submit' => ['submit'];
        yield 'cancel' => ['cancel'];
    }

    #[DataProvider('companyTransitions')]
    public function testBuyerCanSubmitAndCancelTheirOwnCompanysOrder(string $transition): void
    {
        $company = $this->aCompany(1);
        $attribute = OrderVoter::attributeForTransition($transition);

        self::assertGranted($this->vote($this->aBuyer($company), $attribute, new Order($company)));
    }

    #[DataProvider('companyTransitions')]
    public function testBuyerCannotSubmitOrCancelAnotherCompanysOrder(string $transition): void
    {
        $buyer = $this->aBuyer($this->aCompany(1));
        $attribute = OrderVoter::attributeForTransition($transition);

        self::assertDenied($this->vote($buyer, $attribute, new Order($this->aCompany(2))));
    }

    public function testUnknownTransitionHasNoAttribute(): void
    {
        self::assertNull(OrderVoter::attributeForTransition('teleport'));
    }

    public function testVoterAbstainsOnUnrelatedSubjectsAndAttributes(): void
    {
        $buyer = $this->aBuyer($this->aCompany(1));
        $token = new UsernamePasswordToken($buyer, 'main', $buyer->getRoles());

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, new \stdClass(), [OrderVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, new Order($this->aCompany(1)), ['ORDER_TELEPORT']));
    }

    private function vote(User $user, string $attribute, Order $order): int
    {
        return $this->voter->vote(new UsernamePasswordToken($user, 'main', $user->getRoles()), $order, [$attribute]);
    }

    private static function assertGranted(int $result): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    private static function assertDenied(int $result): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    private function aCompany(int $id): Company
    {
        $company = new Company();
        (new \ReflectionProperty(Company::class, 'id'))->setValue($company, $id);

        return $company;
    }

    private function aBuyer(?Company $company): User
    {
        return (new User())->setRoles([User::ROLE_BUYER])->setCompany($company);
    }

    private function aValidator(): User
    {
        return (new User())->setRoles([User::ROLE_VALIDATOR]);
    }
}
