<?php

namespace App\Ordering\UI\Controller;

use App\Entity\User;
use App\Ordering\Application\OrderWorkflow;
use App\Ordering\Domain\Entity\Order;
use App\Ordering\Domain\Repository\OrderRepositoryInterface;
use App\Ordering\Domain\Repository\OrderStatusHistoryRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Exception\LogicException as WorkflowLogicException;

/**
 * Order tracking (#28) and staff transition actions (#26). Who's allowed to
 * trigger which transition is a coarse, inline check for now — EPIC 6
 * (Voters) is where this becomes a proper, independently testable
 * authorization layer; nothing here should be considered final.
 */
#[Route('/commandes')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderStatusHistoryRepositoryInterface $history,
        private readonly OrderWorkflow $orderWorkflow,
    ) {
    }

    #[Route('', name: 'app_order_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null !== $user->getCompany()) {
            $orders = $this->orders->findSubmittedForCompany($user->getCompany());
        } elseif ($this->isGranted(User::ROLE_VALIDATOR)) {
            $orders = $this->orders->findAllSubmitted();
        } else {
            $orders = [];
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $order = $this->orders->findById($id);
        if (null === $order || $order->isDraft() || !$this->canView($order)) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $enabledTransitions = array_filter(
            $this->orderWorkflow->enabledTransitions($order),
            fn ($transition) => $this->canTrigger($order, $transition->getName())
        );

        return $this->render('order/show.html.twig', [
            'order' => $order,
            'history' => $this->history->findByOrder($order),
            'enabledTransitions' => $enabledTransitions,
        ]);
    }

    #[Route('/{id}/transition/{transition}', name: 'app_order_transition', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transition(int $id, string $transition, Request $request): Response
    {
        $order = $this->orders->findById($id);
        if (null === $order || $order->isDraft() || !$this->canView($order)) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!$this->isCsrfTokenValid('order_transition_'.$order->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$this->canTrigger($order, $transition)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas effectuer cette action.');
        }

        try {
            $this->orderWorkflow->apply($order, $transition);
            $this->addFlash('success', 'Commande mise à jour.');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }

    private function canView(Order $order): bool
    {
        if ($this->isGranted(User::ROLE_VALIDATOR)) {
            return true;
        }

        /** @var User $user */
        $user = $this->getUser();

        return null !== $user->getCompany() && $user->getCompany()->getId() === $order->getCompany()->getId();
    }

    /**
     * Staff (ROLE_VALIDATOR) drive the fulfillment pipeline; the owning
     * company can only cancel its own order.
     */
    private function canTrigger(Order $order, string $transition): bool
    {
        if ($this->isGranted(User::ROLE_VALIDATOR)) {
            return true;
        }

        return 'cancel' === $transition && $this->canView($order);
    }
}
