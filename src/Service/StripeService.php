<?php

namespace App\Service;

use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private StripeClient $stripe;
    private string $publicKey;
    private string $appUrl;

    public function __construct(StripeClient $stripe, string $publicKey, string $appUrl)
    {
        $this->stripe = $stripe;
        $this->publicKey = $publicKey;
        $this->appUrl = $appUrl;
    }

    /**
     * Créer une session de paiement pour une commande
     */
    public function createOrderCheckoutSession(array $items, string $orderId, string $email): string
    {
        $lineItems = [];
        $totalAmount = 0;

        foreach ($items as $item) {
            $productData = [
                'name' => $item['name'],
            ];
            
            // N'ajouter l'image que si elle existe et est valide
            if (!empty($item['image']) && filter_var($item['image'], FILTER_VALIDATE_URL)) {
                $productData['images'] = [$item['image']];
            }
            
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => $productData,
                    'unit_amount' => (int)($item['price'] * 100), // Montant en centimes
                ],
                'quantity' => $item['quantity'],
            ];
            $totalAmount += $item['price'] * $item['quantity'];
        }

        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'customer_email' => $email,
            'success_url' => $this->appUrl . '/stripe/commande/payment-success?session_id={CHECKOUT_SESSION_ID}&order_id=' . $orderId,
            'cancel_url' => $this->appUrl . '/stripe/commande/payment-cancel',
            'metadata' => [
                'order_id' => $orderId,
                'user_email' => $email,
            ],
        ]);

        return $session->id;
    }

    /**
     * Créer une session de paiement pour un abonnement premium
     */
    public function createSubscriptionCheckoutSession(string $subscriptionType, string $userEmail, string $userId): string
    {
        $prices = [
            'monthly' => 9.99,
            'yearly' => 99.99,
        ];

        $price = $prices[$subscriptionType] ?? 9.99;
        $displayName = $subscriptionType === 'monthly' ? 'Abonnement Premium - Mensuel' : 'Abonnement Premium - Annuel';

        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $displayName,
                            'description' => 'Accès illimité aux fonctionnalités premium',
                        ],
                        'unit_amount' => (int)($price * 100),
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'customer_email' => $userEmail,
            'success_url' => $this->appUrl . '/subscription/payment-success?session_id={CHECKOUT_SESSION_ID}&user_id=' . $userId . '&type=' . $subscriptionType,
            'cancel_url' => $this->appUrl . '/premium',
            'metadata' => [
                'user_id' => $userId,
                'subscription_type' => $subscriptionType,
                'user_email' => $userEmail,
            ],
        ]);

        return $session->id;
    }

    /**
     * Récupérer une session Stripe
     */
    public function getCheckoutSession(string $sessionId): \Stripe\Checkout\Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);
    }

    /**
     * Vérifier le statut du paiement
     */
    public function getPaymentStatus(string $sessionId): string
    {
        $session = $this->getCheckoutSession($sessionId);
        return $session->payment_status;
    }

    /**
     * Récupérer la clé publique
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Valider un webhook Stripe
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $webhookSecret): array
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
            return ['success' => true, 'event' => $event];
        } catch (\UnhandledMatchError $e) {
            return ['success' => false, 'error' => 'Invalid signature'];
        }
    }
}
