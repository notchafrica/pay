<?php

declare(strict_types=1);

namespace Notch\Framework\Services\Invoice;

use App\Models\Invoice\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

final class InvoiceVersioningService
{
    public function createRevision(Invoice $invoice, array $changes = []): void
    {
        $currentVersion = $invoice->versions()->count() + 1;
        $path = sprintf(
            'invoices/%s/%s/%s_v%s.pdf',
            $invoice->created_at->format('Y'),
            $invoice->created_at->format('m'),
            $invoice->invoice_number,
            $currentVersion
        );

        Pdf::view('pdf.invoices.default', [
            'invoice' => $invoice,
            'company' => $invoice->team,
            'version' => $currentVersion,
        ])
            ->save($path);

        $invoice->versions()->create([
            'version' => $currentVersion,
            'path' => $path,
            'created_by' => auth()->id(),
            'changes' => $changes,
        ]);
    }
}
