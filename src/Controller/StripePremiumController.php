<?php

namespace App\Controller;

use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[Route('/subscription', name: 'subscription_')]
class StripePremiumController extends AbstractController
{
    #[Route('/create-checkout-session', name: 'create_checkout', methods: ['POST'])]
    public function createCheckoutSession(Request $request, StripeService $stripeService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $subscriptionType = $data['subscription_type'] ?? 'monthly';

        if (!in_array($subscriptionType, ['monthly', 'yearly'])) {
            return $this->json(['error' => 'Type d\'abonnement invalide'], 400);
        }

        try {
            $sessionId = $stripeService->createSubscriptionCheckoutSession(
                $subscriptionType,
                $user->getEmail(),
                (string)$user->getId()
            );

            return $this->json([
                'success' => true,
                'sessionId' => $sessionId,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/payment-success', name: 'payment_success')]
    public function paymentSuccess(Request $request, StripeService $stripeService, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $sessionId = $request->query->get('session_id');
        $userId = $request->query->get('user_id');
        $subscriptionType = $request->query->get('type');

        if (!$sessionId || !$userId || !$subscriptionType) {
            $this->addFlash('error', 'Données de paiement manquantes');
            return $this->redirectToRoute('app_premium');
        }

        try {
            // Vérifier le statut du paiement
            $paymentStatus = $stripeService->getPaymentStatus($sessionId);

            if ($paymentStatus === 'paid') {
                // Récupérer l'utilisateur
                $user = $em->getRepository('App\Entity\User')->find($userId);
                
                if ($user) {
                    // Activer le statut premium
                    $user->setIsPremium(true);
                    
                    // Calculer les dates
                    $startDate = new \DateTime();
                    $endDate = new \DateTime();
                    $price = '9.99';
                    
                    if ($subscriptionType === 'monthly') {
                        $endDate->modify('+1 month');
                        $price = '9.99';
                    } else {
                        $endDate->modify('+1 year');
                        $price = '99.99';
                    }
                    
                    $user->setPremiumExpiresAt($endDate);
                    
                    // Créer un enregistrement dans la table subscription
                    $subscription = new \App\Entity\Subscription();
                    $subscription->setUser($user);
                    $subscription->setType($subscriptionType);
                    $subscription->setStartDate($startDate);
                    $subscription->setEndDate($endDate);
                    $subscription->setPrice($price);
                    $subscription->setActive(true);
                    $subscription->setStatut('active');
                    
                    $em->persist($subscription);
                    $em->flush();

                    // Envoyer l'email de confirmation
                    try {
                        $email = (new TemplatedEmail())
                            ->from('noreply@bibliotech.com')
                            ->to($user->getEmail())
                            ->subject('🎉 Confirmation de votre abonnement Premium - BiblioTech')
                            ->htmlTemplate('emails/premium_confirmation.html.twig')
                            ->context([
                                'user' => $user,
                                'subscription' => $subscription,
                                'app_url' => $this->getParameter('app.url') ?? 'https://127.0.0.1:8000'
                            ]);
                        
                        $mailer->send($email);
                    } catch (\Exception $e) {
                        // Ne pas bloquer si l'email échoue
                        error_log('Erreur envoi email premium: ' . $e->getMessage());
                    }

                    $this->addFlash('success', 'Paiement réussi ! Votre abonnement Premium est maintenant actif. Un email de confirmation vous a été envoyé.');
                    return $this->redirectToRoute('subscription_index');
                }
            }

            $this->addFlash('error', 'Le paiement n\'a pas été complété');
            return $this->redirectToRoute('subscription_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la vérification du paiement: ' . $e->getMessage());
            return $this->redirectToRoute('subscription_index');
        }
    }

    #[Route('/payment-cancel', name: 'payment_cancel')]
    public function paymentCancel(): Response
    {
        $this->addFlash('info', 'Paiement annulé');
        return $this->redirectToRoute('app_premium');
    }
}
