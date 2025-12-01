<?php

namespace App\Service;

use App\Entity\Livre;
use App\Entity\Panier;
use App\Entity\PanierItem;
use App\Entity\User;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;

class PanierService
{
    public function __construct(
        private PanierRepository $panierRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Obtenir ou créer le panier de l'utilisateur
     */
    public function getPanierOrCreate(User $user): Panier
    {
        $panier = $this->panierRepository->findByUser($user);

        if (!$panier) {
            $panier = new Panier();
            $panier->setUser($user);
            $this->entityManager->persist($panier);
            $this->entityManager->flush();
        }

        return $panier;
    }

    /**
     * Ajouter un livre au panier
     */
    public function addToCart(Panier $panier, Livre $livre, int $quantite = 1): Panier
    {
        // Vérifier la disponibilité en stock
        if ($livre->getQte() <= 0) {
            throw new \RuntimeException('Ce livre n\'est plus en stock.');
        }

        // Vérifier si le livre est déjà dans le panier
        $existingItem = null;
        foreach ($panier->getItems() as $item) {
            if ($item->getLivre()->getId() === $livre->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            // Vérifier que la nouvelle quantité totale ne dépasse pas le stock disponible
            $nouvelleQuantite = $existingItem->getQuantite() + $quantite;
            if ($nouvelleQuantite > $livre->getQte()) {
                throw new \RuntimeException('La quantité demandée dépasse le stock disponible. Stock disponible: ' . $livre->getQte());
            }
            // Augmenter la quantité
            $existingItem->setQuantite($nouvelleQuantite);
        } else {
            // Vérifier que la quantité demandée ne dépasse pas le stock disponible
            if ($quantite > $livre->getQte()) {
                throw new \RuntimeException('La quantité demandée dépasse le stock disponible. Stock disponible: ' . $livre->getQte());
            }
            // Créer un nouvel item
            $item = new PanierItem();
            $item->setLivre($livre);
            $item->setQuantite($quantite);
            $item->setPrixUnitaire($livre->getPrix());
            $panier->addItem($item);
        }

        $panier->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $panier;
    }

    /**
     * Mettre à jour la quantité d'un article
     */
    public function updateItemQuantity(PanierItem $item, int $quantite): void
    {
        if ($quantite <= 0) {
            $this->removeFromCart($item->getPanier(), $item);
        } else {
            // Vérifier que la quantité ne dépasse pas le stock disponible
            $livre = $item->getLivre();
            if ($quantite > $livre->getQte()) {
                throw new \RuntimeException('La quantité demandée dépasse le stock disponible. Stock disponible: ' . $livre->getQte());
            }
            $item->setQuantite($quantite);
            $item->getPanier()->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }
    }

    /**
     * Supprimer un article du panier
     */
    public function removeFromCart(Panier $panier, PanierItem $item): void
    {
        $panier->removeItem($item);
        $this->entityManager->remove($item);
        $panier->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Vider le panier
     */
    public function clearCart(Panier $panier): void
    {
        foreach ($panier->getItems() as $item) {
            $panier->removeItem($item);
            $this->entityManager->remove($item);
        }
        $panier->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
