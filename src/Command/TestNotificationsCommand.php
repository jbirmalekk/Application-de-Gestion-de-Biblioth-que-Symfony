<?php

namespace App\Command;

use App\Entity\Livre;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test:notifications',
    description: 'Test notification system by changing book stock',
)]
class TestNotificationsCommand extends Command
{
    public function __construct(
        private LivreRepository $livreRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find first book
        $livre = $this->livreRepository->findOneBy([]);
        
        if (!$livre) {
            $io->error('No books found in database');
            return Command::FAILURE;
        }

        $io->info(sprintf('Testing with book: %s (ID: %d)', $livre->getTitre(), $livre->getId()));
        $io->info(sprintf('Current stock: %d', $livre->getQte()));

        // Test 1: Change stock to 0
        $io->section('Test 1: Setting stock to 0');
        $oldQte = $livre->getQte();
        $livre->setQte(0);
        $this->entityManager->persist($livre);
        $this->entityManager->flush();
        $io->success(sprintf('Stock changed from %d to 0. Check notifications table!', $oldQte));

        // Test 2: Change stock back
        $io->section('Test 2: Setting stock back to > 0');
        $livre->setQte($oldQte);
        $this->entityManager->persist($livre);
        $this->entityManager->flush();
        $io->success(sprintf('Stock changed back to %d. Check notifications table!', $oldQte));

        return Command::SUCCESS;
    }
}
