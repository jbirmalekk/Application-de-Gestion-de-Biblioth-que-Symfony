<?php

namespace App\EventListener;

use App\Entity\Livre;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class NewLivreNotificationListener implements EventSubscriberInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Livre) {
            return;
        }

        // Notify all users that a new book has been added
        $this->notifyNewBook($entity);
    }

    private function notifyNewBook(Livre $livre): void
    {
        // Find all users (or optionally only users with specific role)
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $this->notificationService->createForUser(
                $user,
                sprintf('📚 Nouveau livre ajouté: "%s" par %s', 
                    $livre->getTitre(),
                    $livre->getAuteurs()->isEmpty() 
                        ? 'Auteur inconnu'
                        : $livre->getAuteurs()->first()->getPrenom() . ' ' . $livre->getAuteurs()->first()->getNom()
                ),
                'new_book',
                $livre
            );
        }
    }
}
