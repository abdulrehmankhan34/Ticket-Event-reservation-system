<?php

namespace App\MessageHandler;

use App\Entity\ETicket;
use App\Message\GenerateEticketMessage;
use App\Repository\BookingItemRepository;
use App\Repository\ETicketRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateEticketMessageHandler
{
    public function __construct(
        private readonly BookingItemRepository $bookingItems,
        private readonly ETicketRepository $tickets,
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function __invoke(GenerateEticketMessage $message): void
    {
        $item = $this->bookingItems->find($message->bookingItemId);
        if (!$item) {
            return;
        }

        if ($this->tickets->findOneByBookingItem($item)) {
            return; // idempotent
        }

        $qrToken = bin2hex(random_bytes(16));

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrToken)
            ->size(260)
            ->margin(10)
            ->build();

        $qrDataUri = $result->getDataUri();

        $event = $item->getTier()->getEvent();
        $html = <<<HTML
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
    .box { border: 1px solid #ddd; padding: 14px; }
    h1 { font-size: 16px; margin: 0 0 10px 0; }
    .muted { color: #555; }
    .qr { margin-top: 12px; }
    .token { font-family: monospace; }
  </style>
</head>
<body>
  <div class="box">
    <h1>E‑Ticket</h1>
    <div><strong>Event:</strong> {$event->getName()}</div>
    <div><strong>Tier:</strong> {$item->getTier()->getName()}</div>
    <div><strong>Quantity:</strong> {$item->getQuantity()}</div>
    <div class="muted"><strong>QR Token:</strong> <span class="token">{$qrToken}</span></div>
    <div class="qr">
      <img src="{$qrDataUri}" alt="QR Code">
    </div>
  </div>
</body>
</html>
HTML;

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $dir = $this->projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'etickets';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $filename = sprintf('eticket_booking_item_%d_%s.pdf', $item->getId(), $qrToken);
        $relativePath = 'var/etickets/' . $filename;
        $absolutePath = $dir . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($absolutePath, $dompdf->output());

        $ticket = new ETicket($item, $qrToken, $relativePath);
        $this->em->persist($ticket);
        $this->em->flush();
    }
}

