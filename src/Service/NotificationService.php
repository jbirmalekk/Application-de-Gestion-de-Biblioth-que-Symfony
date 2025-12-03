<?php

namespace App\Service;

use App\Entity\Livre;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createForUser(User $user, string $message, string $type, ?Livre $livre = null): Notification
    {
        $notification = new Notification();
        $notification
            ->setUser($user)
            ->setMessage($message)
            ->setType($type)
            ->setLivre($livre);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    public function markAsRead(Notification $notification): void
    {
        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $this->entityManager->flush();
        }
    }
}


