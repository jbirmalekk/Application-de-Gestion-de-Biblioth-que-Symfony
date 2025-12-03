<?php

namespace App\Controller;

use App\Entity\Coupon;
use App\Form\CouponType;
use App\Repository\CouponRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/coupons', name: 'admin_coupons_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCouponController extends AbstractController
{
    public function __construct(
        private CouponRepository $couponRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $coupons = $this->couponRepository->findAll();

        return $this->render('admin/coupon/index.html.twig', [
            'coupons' => $coupons,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $coupon = new Coupon();
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($coupon);
            $this->entityManager->flush();

            $this->addFlash('success', 'Coupon créé avec succès.');

            return $this->redirectToRoute('admin_coupons_index');
        }

        return $this->render('admin/coupon/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Coupon $coupon, Request $request): Response
    {
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coupon->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Coupon modifié avec succès.');

            return $this->redirectToRoute('admin_coupons_index');
        }

        return $this->render('admin/coupon/edit.html.twig', [
            'coupon' => $coupon,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(Coupon $coupon, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_coupon_' . $coupon->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_coupons_index');
        }

        $this->entityManager->remove($coupon);
        $this->entityManager->flush();

        $this->addFlash('success', 'Coupon supprimé avec succès.');

        return $this->redirectToRoute('admin_coupons_index');
    }
}

