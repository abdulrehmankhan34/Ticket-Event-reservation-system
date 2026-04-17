<?php

namespace App\Controller;

use App\Entity\BookingItem;
use App\Entity\ETicket;
use App\Entity\User;
use App\Repository\ETicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ETicketController extends AbstractController
{
    #[Route('/e-tickets/{id}.pdf', name: 'app_eticket_download', methods: ['GET'])]
    public function download(BookingItem $id, ETicketRepository $tickets): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($id->getBooking()->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $ticket = $tickets->findOneByBookingItem($id);
        if (!$ticket instanceof ETicket) {
            return new Response('E-ticket is still being generated. Please refresh in a few seconds.', 202);
        }

        $path = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . $ticket->getFilePath();
        if (!is_file($path)) {
            return new Response('E-ticket file not found yet. Please refresh.', 202);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Disposition', 'attachment; filename="e-ticket-'.$id->getId().'.pdf"');
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}

