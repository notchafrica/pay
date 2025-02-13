<?php

declare(strict_types=1);

namespace Notch\Framework\Services\Invoice;

use App\Models\Invoice\Invoice;
use Illuminate\Support\Facades\Storage;

final class InvoiceRetentionService
{
    public function archive(): void
    {
        //
        $cutoffDate = now()->subYears(10);

        Invoice::with('versions')
            ->where('created_at', '<', $cutoffDate)
            ->chunk(100, function ($invoices): void {
                foreach ($invoices as $invoice) {
                    $this->archiveInvoice($invoice);
                }
            });
    }

    protected function archiveInvoice(Invoice $invoice): void
    {
        // Copier vers un stockage d'archives moins coûteux
        foreach ($invoice->versions as $version) {
            if (Storage::exists($version->path)) {
                Storage::disk('archives')->put(
                    "invoices_archive/{$version->path}",
                    Storage::get($version->path)
                );
                Storage::delete($version->path);
            }
        }

        $invoice->update(['archived' => true]);
    }
}
