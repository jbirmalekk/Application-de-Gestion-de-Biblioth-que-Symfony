<?php

namespace App\Twig;

use App\Service\WishlistService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WishlistExtension extends AbstractExtension
{
    public function __construct(
        private WishlistService $wishlistService,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wishlist_count', [$this, 'getWishlistCount']),
        ];
    }

    public function getWishlistCount(): int
    {
        $user = $this->security->getUser();
        if (!$user) {
            return 0;
        }

        return $this->wishlistService->getWishlistCount($user);
    }
}

