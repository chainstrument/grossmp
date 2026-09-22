<?php

namespace App\Ordering\Infrastructure\Workflow;

use App\Identity\Domain\Entity\User;
use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Entity\OrderStatusHistory;
use App\Ordering\Domain\Enum\OrderStatus;
use App\Ordering\Domain\Repository\OrderStatusHistoryRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Guards (#25) and side effects (#27) for the "order" workflow
 * (config/packages/workflow.yaml). Deliberately kept in Infrastructure, not
 * Domain: it depends on Symfony's Workflow events and Security, neither of
 * which the domain layer should know about.
 */
class OrderWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrderStatusHistoryRepositoryInterface $history,
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.order.guard.submit' => 'guardSubmit',
            'workflow.order.guard.prepare' => 'guardPrepare',
            'workflow.order.completed' => 'recordHistory',
            'workflow.order.completed.prepare' => 'consumeStock',
        ];
    }

    /**
     * Can't submit an empty cart.
     */
    public function guardSubmit(GuardEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getSubject();

        if ($order->isEmpty()) {
            $event->setBlocked(true, 'Impossible de valider une commande vide.');
        }
    }

    /**
     * Stock may have moved (other orders, a manual correction...) since the
     * lines were added to the cart — re-checked here, right before it's
     * actually committed to preparation.
     */
    public function guardPrepare(GuardEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getSubject();

        foreach ($order->getLines() as $line) {
            if (!$line->getProduct()->isInStock($line->getQuantity())) {
                $event->setBlocked(true, sprintf(
                    'Stock insuffisant pour "%s" (%d disponible(s), %d demandé(s)).',
                    $line->getProduct()->getName(),
                    $line->getProduct()->getStockQuantity(),
                    $line->getQuantity()
                ));

                return;
            }
        }
    }

    /**
     * Every completed transition gets logged, regardless of which one it was.
     */
    public function recordHistory(CompletedEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getSubject();
        $transition = $event->getTransition();

        $user = $this->security->getUser();

        $entry = new OrderStatusHistory(
            $order,
            OrderStatus::from($transition->getFroms()[0]),
            OrderStatus::from($transition->getTos()[0]),
            $transition->getName(),
            $user instanceof User ? $user : null,
        );

        $this->history->add($entry);
    }

    /**
     * The guard already confirmed there's enough stock; this is where that
     * stock actually gets committed to the order (once, right here — not
     * when the line was added to the cart).
     */
    public function consumeStock(CompletedEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getSubject();

        foreach ($order->getLines() as $line) {
            $line->getProduct()->adjustStock(-$line->getQuantity());
        }
    }
}
