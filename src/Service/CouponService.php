<?php

namespace App\Service;

use App\Entity\Coupon;
use App\Repository\CouponRepository;
use Doctrine\ORM\EntityManagerInterface;

class CouponService
{
    public function __construct(
        private CouponRepository $couponRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Valider et appliquer un coupon sur un montant
     *
     * @return array ['valid' => bool, 'coupon' => Coupon|null, 'remise' => float, 'message' => string]
     */
    public function validerEtAppliquer(string $code, float $montant): array
    {
        $coupon = $this->couponRepository->findActiveByCode($code);

        if (!$coupon) {
            return [
                'valid' => false,
                'coupon' => null,
                'remise' => 0.0,
                'message' => 'Code promo invalide ou inactif.',
            ];
        }

        if (!$coupon->isValid()) {
            return [
                'valid' => false,
                'coupon' => $coupon,
                'remise' => 0.0,
                'message' => 'Ce code promo a expiré ou a atteint sa limite d\'utilisation.',
            ];
        }

        if ($coupon->getMontantMinimum() && $montant < (float) $coupon->getMontantMinimum()) {
            return [
                'valid' => false,
                'coupon' => $coupon,
                'remise' => 0.0,
                'message' => sprintf('Ce code promo nécessite un montant minimum de %s €.', number_format((float) $coupon->getMontantMinimum(), 2, ',', ' ')),
            ];
        }

        $remise = $coupon->calculerRemise($montant);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'remise' => $remise,
            'message' => $coupon->getDescription() ?? 'Code promo appliqué avec succès.',
        ];
    }

    /**
     * Incrémenter l'usage d'un coupon
     */
    public function incrementerUsage(Coupon $coupon): void
    {
        $coupon->incrementUsage();
        $this->entityManager->flush();
    }
}

