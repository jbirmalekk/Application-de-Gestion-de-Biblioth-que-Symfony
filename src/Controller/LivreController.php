<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Livre;
use App\Form\AvisType;
use App\Form\LivreType;
use App\Repository\AvisRepository;
use App\Repository\CommandeItemRepository;
use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use App\Repository\WishlistRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/livre')]
final class LivreController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService,
        private WishlistRepository $wishlistRepository,
        private UserRepository $userRepository,
    ) {
    }

    #[Route(name: 'app_livre_index', methods: ['GET'])]
    public function index(LivreRepository $livreRepository): Response
    {
        return $this->render('livre/index.html.twig', [
            'livres' => $livreRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_livre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traiter l'upload d'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/assets/img/books',
                    $newFilename
                );

                $livre->setImage($newFilename);
            }

            // Traiter l'upload du PDF
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile) {
                $originalPdfName = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safePdfName = $slugger->slug($originalPdfName);
                $newPdfFilename = $safePdfName.'-'.uniqid().'.'.$pdfFile->guessExtension();

                $pdfFile->move(
                    $this->getParameter('kernel.project_dir').'/public/assets/pdf/books',
                    $newPdfFilename
                );

                $livre->setPdfPath('assets/pdf/books/'.$newPdfFilename);
            }

            $entityManager->persist($livre);
            $entityManager->flush();

            // Notification "nouveau livre" pour tous les utilisateurs
            $users = $this->userRepository->findAll();
            foreach ($users as $user) {
                $this->notificationService->createForUser(
                    $user,
                    sprintf('Un nouveau livre "%s" a été ajouté au catalogue.', $livre->getTitre()),
                    'new_book',
                    $livre
                );
            }

            return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre/new.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livre_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        Livre $livre,
        AvisRepository $avisRepository,
        EntityManagerInterface $entityManager,
        CommandeItemRepository $commandeItemRepository,
    ): Response {
        $avisApprouves = $avisRepository->findApprouvesByLivre($livre);
        $noteMoyenne = $avisRepository->getNoteMoyenne($livre);
        $nombreAvis = $avisRepository->countApprouvesByLivre($livre);

        // Vérifier si l'utilisateur a déjà laissé un avis
        $userHasReview = false;
        $existingAvis = null;
        if ($this->getUser()) {
            $existingAvis = $avisRepository->findOneBy([
                'livre' => $livre,
                'user' => $this->getUser(),
            ]);
            $userHasReview = $existingAvis !== null;
        }

        // Vérifier si l'utilisateur peut télécharger le PDF
        $canDownloadPdf = false;
        if ($livre->getPdfPath() && $this->getUser()) {
            // Règle simple: livre gratuit OU déjà acheté
            if ($livre->getPrix() === null || $livre->getPrix() <= 0) {
                $canDownloadPdf = true;
            } else {
                $user = $this->getUser();
                $items = $commandeItemRepository->createQueryBuilder('ci')
                    ->join('ci.commande', 'c')
                    ->andWhere('ci.livre = :livre')
                    ->andWhere('c.user = :user')
                    ->setParameter('livre', $livre)
                    ->setParameter('user', $user)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getResult();

                $canDownloadPdf = \count($items) > 0;
            }
        }

        // Gérer le formulaire d'avis
        $avis = new Avis();
        $avis->setLivre($livre);
        if ($this->getUser()) {
            $avis->setUser($this->getUser());
        }

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !$userHasReview) {
            // Synchroniser les étoiles avec la note
            $avis->setEtoiles($avis->getNote());
            // Par défaut, l'avis n'est pas approuvé
            $avis->setApprouve(false);

            $entityManager->persist($avis);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été soumis avec succès. Il sera publié après modération par un administrateur.');

            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        return $this->render('livre/show.html.twig', [
            'livre' => $livre,
            'avis' => $avisApprouves,
            'note_moyenne' => $noteMoyenne,
            'nombre_avis' => $nombreAvis,
            'form' => $form,
            'user_has_review' => $userHasReview,
            'can_download_pdf' => $canDownloadPdf,
        ]);
    }

    #[Route('/{id}/download-pdf', name: 'app_livre_download_pdf', methods: ['GET'])]
    public function downloadPdf(Livre $livre, CommandeItemRepository $commandeItemRepository): Response
    {
        $pdfPath = $livre->getPdfPath();
        if (!$pdfPath) {
            throw $this->createNotFoundException('Aucun fichier PDF disponible pour ce livre.');
        }

        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour télécharger ce fichier.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier les droits (gratuit ou déjà acheté)
        $canDownload = false;
        if ($livre->getPrix() === null || $livre->getPrix() <= 0) {
            $canDownload = true;
        } else {
            $user = $this->getUser();
            $items = $commandeItemRepository->createQueryBuilder('ci')
                ->join('ci.commande', 'c')
                ->andWhere('ci.livre = :livre')
                ->andWhere('c.user = :user')
                ->setParameter('livre', $livre)
                ->setParameter('user', $user)
                ->setMaxResults(1)
                ->getQuery()
                ->getResult();

            $canDownload = \count($items) > 0;
        }

        if (!$canDownload) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce fichier (livre non gratuit ou non acheté).');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        $absolutePath = $this->getParameter('kernel.project_dir').'/public/'.$pdfPath;
        if (!is_file($absolutePath)) {
            throw $this->createNotFoundException('Fichier PDF introuvable sur le serveur.');
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf('%s.pdf', $livre->getTitre() ?: 'livre')
        );

        return $response;
    }

    #[Route('/{id}/edit', name: 'app_livre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Livre $livre, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $oldQte = $livre->getQte();

        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traiter l'upload d'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/assets/img/books',
                    $newFilename
                );

                $livre->setImage($newFilename);
            }

            // Traiter l'upload du PDF
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile) {
                $originalPdfName = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safePdfName = $slugger->slug($originalPdfName);
                $newPdfFilename = $safePdfName.'-'.uniqid().'.'.$pdfFile->guessExtension();

                $pdfFile->move(
                    $this->getParameter('kernel.project_dir').'/public/assets/pdf/books',
                    $newPdfFilename
                );

                $livre->setPdfPath('assets/pdf/books/'.$newPdfFilename);
            }

            $entityManager->flush();

            // Si le livre passe de en stock -> rupture, notifier les utilisateurs qui l'ont en favoris
            $newQte = $livre->getQte();
            if ($oldQte > 0 && $newQte === 0) {
                $wishlistItems = $this->wishlistRepository->findByLivre($livre);
                foreach ($wishlistItems as $item) {
                    $user = $item->getUser();
                    $this->notificationService->createForUser(
                        $user,
                        sprintf('Le livre "%s" est maintenant en rupture de stock.', $livre->getTitre()),
                        'wishlist_out_of_stock',
                        $livre
                    );
                }
            }

            return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre/edit.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livre_delete', methods: ['POST'])]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($livre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
    }
}
