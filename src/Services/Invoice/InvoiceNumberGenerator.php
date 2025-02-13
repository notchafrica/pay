<?php

declare(strict_types=1);

namespace Notch\Framework\Services\Invoice;

use App\Models\Invoice\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class InvoiceNumberGenerator
{
    /**
     * Générer un nouveau numéro de facture pour une team
     */
    public function generate(int $teamId, ?string $prefix = null, $sequence = null): array
    {
        return DB::transaction(function () use ($teamId, $prefix, $sequence) {
            $currentDate = Carbon::now();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');

            // Déterminer le préfixe
            $prefix = $prefix ?: $this->getDefaultPrefix($teamId);

            // Obtenir le dernier numéro de séquence pour la team
            $lastInvoice = Invoice::where('team_id', $teamId)
                ->where('invoice_number', 'like', "{$prefix}{$year}{$month}%")
                ->orderBy('sequence_number', 'desc')
                ->first();

            if ($sequence) {
                $sequence++;
            } else {
                $sequence = $lastInvoice ? ($lastInvoice->sequence_number + 1) : 1;
            }

            // Formater le numéro de séquence sur 4 chiffres
            $sequenceFormatted = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            // Construire le numéro de facture
            $invoiceNumber = "{$prefix}{$year}{$month}{$sequenceFormatted}";

            // Vérifier si le numéro est unique dans la team
            if ($this->exists($invoiceNumber, $teamId)) {
                return $this->generate($teamId, $prefix, $sequence);
            }

            return [
                'invoice_number' => $invoiceNumber,
                'sequence_number' => $sequence,
            ];
        }, 5); // 5 tentatives en cas de deadlock
    }

    /**
     * Obtenir le préfixe par défaut pour une team
     */
    protected function getDefaultPrefix(int $teamId): string
    {
        // Option 1: Préfixe simple avec ID de la team
        return 'INV-'.str_pad((string) $teamId, 3, '0', STR_PAD_LEFT).'-';

        // Option 2: Vous pouvez aussi récupérer un préfixe personnalisé depuis la table teams
        // $team = Team::find($teamId);
        // return $team->invoice_prefix ?: 'INV-' . str_pad($teamId, 3, '0', STR_PAD_LEFT) . '-';
    }

    /**
     * Vérifier si un numéro de facture existe déjà dans une team
     */
    public function exists(string $number, int $teamId): bool
    {
        return Invoice::where('team_id', $teamId)
            ->where('invoice_number', $number)
            ->exists();
    }

    /**
     * Valider un format de numéro de facture pour une team
     */
    public function isValidFormat(string $number, int $teamId): bool
    {
        $prefix = $this->getDefaultPrefix($teamId);
        // Format : INV-001-YYYYMMXXXX
        $pattern = '/^'.preg_quote($prefix).'\d{10}$/';

        return (bool) preg_match($pattern, $number);
    }
}
