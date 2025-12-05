<?php

namespace App\Controller;

use App\Service\SubscriptionService;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subscription', name: 'subscription_')]
#[IsGranted('ROLE_USER')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private StripeService $stripeService
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $activeSubscription = $user->getActiveSubscription();

        return $this->render('subscription/index.html.twig', [
            'activeSubscription' => $activeSubscription,
            'monthlyPrice' => $this->subscriptionService->getPrice('monthly'),
            'yearlyPrice' => $this->subscriptionService->getPrice('yearly'),
            'stripe_public_key' => $this->stripeService->getPublicKey(),
        ]);
    }

    #[Route('/subscribe/{type}', name: 'subscribe', methods: ['POST'])]
    public function subscribe(string $type, Request $request): Response
    {
        if (!in_array($type, ['monthly', 'yearly'])) {
            $this->addFlash('error', 'Type d\'abonnement invalide.');
            return $this->redirectToRoute('subscription_index');
        }

        $user = $this->getUser();

        // Vérifier si l'utilisateur a déjà un abonnement actif
        if ($user->hasActiveSubscription()) {
            $this->addFlash('warning', 'Vous avez déjà un abonnement actif.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscription = $this->subscriptionService->createSubscription($user, $type);
            $this->addFlash('success', sprintf(
                'Abonnement %s activé avec succès! Valable jusqu\'au %s',
                $type === 'monthly' ? 'mensuel' : 'annuel',
                $subscription->getEndDate()->format('d/m/Y')
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la souscription.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    #[Route('/renew', name: 'renew', methods: ['POST'])]
    public function renew(): Response
    {
        $user = $this->getUser();
        $subscription = $user->getActiveSubscription();

        if (!$subscription) {
            $this->addFlash('error', 'Aucun abonnement actif trouvé.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $this->subscriptionService->renewSubscription($subscription);
            $this->addFlash('success', 'Abonnement renouvelé avec succès!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du renouvellement.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    #[Route('/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(): Response
    {
        $user = $this->getUser();
        $subscription = $user->getActiveSubscription();

        if (!$subscription) {
            $this->addFlash('error', 'Aucun abonnement actif trouvé.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $this->subscriptionService->cancelSubscription($subscription);
            $this->addFlash('success', 'Abonnement annulé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'annulation.');
        }

        return $this->redirectToRoute('subscription_index');
    }
}
