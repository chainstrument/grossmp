<?php

namespace App\Ordering\UI\Controller;

use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Entity\User;
use App\Ordering\Application\AddToCartRequest;
use App\Ordering\Application\CartManager;
use App\Ordering\Application\OrderWorkflow;
use App\Ordering\Domain\Exception\InsufficientStockException;
use App\Ordering\UI\Form\AddToCartType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Exception\LogicException as WorkflowLogicException;

#[Route('/panier')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartManager $cartManager,
        private readonly OrderWorkflow $orderWorkflow,
    ) {
    }

    #[Route('', name: 'app_cart_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $user = $this->currentUserOrRedirect();
        if ($user instanceof Response) {
            return $user;
        }

        $cart = $this->cartManager->getOrCreateDraftCart($user->getCompany(), $user);

        $form = $this->createForm(AddToCartType::class, new AddToCartRequest());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddToCartRequest $data */
            $data = $form->getData();

            try {
                $this->cartManager->addProduct($cart, $data->product->getId(), $data->quantity);
                $this->addFlash('success', 'Produit ajouté au panier.');

                return $this->redirectToRoute('app_cart_index');
            } catch (InsufficientStockException $e) {
                $form->get('quantity')->addError(new FormError($e->getMessage()));
            } catch (ProductNotFoundException) {
                $form->get('product')->addError(new FormError('Produit introuvable.'));
            }
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'form' => $form,
        ]);
    }

    #[Route('/lignes/{id}/quantite', name: 'app_cart_line_update', methods: ['POST'])]
    public function updateLineQuantity(int $id, Request $request): Response
    {
        $user = $this->currentUserOrRedirect();
        if ($user instanceof Response) {
            return $user;
        }

        if (!$this->isCsrfTokenValid('cart_line_update_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $cart = $this->cartManager->getOrCreateDraftCart($user->getCompany(), $user);
        $quantity = $request->request->getInt('quantity');

        try {
            $this->cartManager->changeLineQuantity($cart, $id, $quantity);
        } catch (InsufficientStockException|\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/lignes/{id}/supprimer', name: 'app_cart_line_remove', methods: ['POST'])]
    public function removeLine(int $id, Request $request): Response
    {
        $user = $this->currentUserOrRedirect();
        if ($user instanceof Response) {
            return $user;
        }

        if (!$this->isCsrfTokenValid('cart_line_remove_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $cart = $this->cartManager->getOrCreateDraftCart($user->getCompany(), $user);

        try {
            $this->cartManager->removeLine($cart, $id);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/recapitulatif', name: 'app_cart_summary', methods: ['GET'])]
    public function summary(): Response
    {
        $user = $this->currentUserOrRedirect();
        if ($user instanceof Response) {
            return $user;
        }

        $cart = $this->cartManager->getOrCreateDraftCart($user->getCompany(), $user);

        return $this->render('cart/summary.html.twig', [
            'cart' => $cart,
            'canSubmit' => $this->orderWorkflow->can($cart, 'submit'),
        ]);
    }

    #[Route('/valider', name: 'app_cart_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $user = $this->currentUserOrRedirect();
        if ($user instanceof Response) {
            return $user;
        }

        if (!$this->isCsrfTokenValid('cart_submit', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $cart = $this->cartManager->getOrCreateDraftCart($user->getCompany(), $user);

        try {
            $this->orderWorkflow->apply($cart, 'submit');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_cart_summary');
        }

        $this->addFlash('success', 'Commande envoyée.');

        return $this->redirectToRoute('app_order_show', ['id' => $cart->getId()]);
    }

    private function currentUserOrRedirect(): User|Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null === $user->getCompany()) {
            $this->addFlash('error', 'Votre compte n\'est rattaché à aucune société : impossible d\'avoir un panier.');

            return $this->redirectToRoute('app_catalog_index');
        }

        return $user;
    }
}
