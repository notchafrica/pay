<?php

declare(strict_types=1);

namespace Notch\Framework\Services\Invoice;

use App\Mail\Invoice\InvoiceMail;
use App\Models\Customer\Customer;
use App\Models\Invoice\Invoice;
use App\Models\Timeline\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

final class InvoiceService
{
    /**
     * Mettre à jour les informations client sur la facture
     */
    public function updateCustomerInformation(Invoice $invoice, string $customerId): Customer
    {
        $customer = Customer::whereUid($customerId)->firstOrFail();

        // Créer un snapshot des informations client
        $customerSnapshot = [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'company_name' => $customer->company_name,
            'tax_number' => $customer->tax_number,
        ];

        $invoice->customer_id = $customer->id;
        $invoice->customer_snapshot = $customerSnapshot;
        $invoice->save();

        return $customer;
    }

    /**
     * Recalculer les totaux de la facture
     */
    public function recalculateTotals(Invoice $invoice): void
    {
        $items = $invoice->items;

        $subTotal = $items->sum('sub_total');
        $taxTotal = $items->sum('tax_amount');
        $total = $subTotal + $taxTotal;

        $invoice->update([
            'sub_total' => $subTotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'amount_due' => $total - $invoice->amount_paid,
        ]);
    }

    /**
     * Enregistrer une activité sur la facture
     */
    public function logActivity(Invoice $invoice, string $action, array $data = []): void
    {
        activity()
            ->on($invoice)
            ->by($data['user'] ?? null)
            ->tap(function (Log $activity) use ($invoice): void {
                $activity->team_id = $invoice->team_id;
            })
            ->withProperties(['team_id' => $invoice->team_id, 'sandbox' => false])
            ->event($action);
    }

    /**
     * Vérifier si une facture peut être modifiée
     */
    public function canEdit(Invoice $invoice): bool
    {
        // An invoice cannot be modified if it has been paid or cancelled.
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return false;
        }

        // An invoice cannot be modified if it has partial payments.
        if ($invoice->amount_paid > 0) {
            return false;
        }

        return true;
    }

    public function send(Invoice $invoice, array $options = []): void
    {
        // Prepare shipping options
        $emailOptions = array_merge([
            'to' => $invoice->customer_email ?? $invoice->customer->email,
            'cc' => null,
            'bcc' => null,
            'subject' => null,
            'message' => null,
            'attach_pdf' => true,
        ], $options);

        // Send email
        Mail::to($invoice->customer_email ?? $invoice->customer->email)->send(new InvoiceMail(
            invoice: $invoice,
            options: $emailOptions
        ));

        // Update invoice status
        $invoice->update([
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        activity()
            ->on($invoice)
            ->by($data['user'] ?? null)
            ->tap(function (Log $activity) use ($invoice): void {
                $activity->team_id = $invoice->team_id;
            })
            ->withProperties(['team_id' => $invoice->team_id, 'sandbox' => false])
            ->event('invoice_sent');

        // $this->logActivity($invoice, 'invoice_sent', ['description' => trans(), ])
    }

    public function generatePDF(Invoice $invoice, bool $draft = false): mixed
    {
        if (! $draft && $invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return $invoice->pdf_path;
        }

        $path = $this->getStoragePath($invoice);

        // Configurer DomPDF
        $pdf = Pdf::loadView('pdf.invoices.default', [
            'invoice' => $invoice,
            'company' => $invoice->team,
        ])->save($path, config('filesystems.default'));

        // Configurer le PDF
        $pdf->setPaper('a4');
        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        Storage::put($path, $pdf->output());

        $this->recordVersion($invoice, $path);

        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    protected function getStoragePath(Invoice $invoice): string
    {
        return sprintf(
            'invoices/%s/%s/%s.pdf',
            $invoice->created_at->format('Y'),
            $invoice->created_at->format('m'),
            $invoice->invoice_number
        );
    }

    protected function recordVersion(Invoice $invoice, string $path): void
    {
        $invoice->versions()->create([
            'version' => $invoice->versions()->count() + 1,
            'path' => $path,
            'created_by' => auth()->id(),
            'changes' => ['type' => 'creation'],
        ]);
    }
}
