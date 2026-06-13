<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\JournalEntry;
use Illuminate\Support\Carbon;

/**
 * Genera el fichero `suenlace.dat` para a3asesor (Wolters Kluwer): texto plano de campos
 * de longitud fija, codificación CP850, registros separados por CRLF.
 *
 * Registros (formato simplificado):
 *   00  cabecera   → NIF, ejercicio, fecha de generación
 *   10  apunte     → fecha, diario, nº asiento, cuenta, concepto, D/H, importe
 *   99  pie        → total de registros
 *
 * Se genera a partir de los asientos (`journal_entries`) del ejercicio indicado.
 */
class SuenlaceExportService
{
    private const CRLF = "\r\n";

    public function build(int $year, ?string $entityId = null, ?string $companyId = null): string
    {
        $nif = $this->nif($companyId);

        $entries = JournalEntry::query()
            ->whereYear('date', $year)
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['lines.account:id,code'])
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $records = [];
        $records[] = $this->header($nif, $year);

        $num = 0;
        foreach ($entries as $entry) {
            $num++;
            foreach ($entry->lines as $line) {
                $debit = (float) $line->debit;
                $credit = (float) $line->credit;
                $isDebit = $debit > 0;
                $records[] = $this->line(
                    date: $entry->date,
                    diary: 1,
                    entryNumber: $num,
                    accountCode: (string) ($line->account?->code ?? ''),
                    concept: (string) $entry->description,
                    debitOrCredit: $isDebit ? 'D' : 'H',
                    amount: $isDebit ? $debit : $credit,
                );
            }
        }

        $records[] = $this->footer(count($records) + 1); // +1 incluye el propio pie

        return mb_convert_encoding(implode(self::CRLF, $records).self::CRLF, 'CP850', 'UTF-8');
    }

    private function header(string $nif, int $year): string
    {
        return '00'
            .$this->padRight($nif, 9)
            .$this->padLeftNum($year, 4)
            .Carbon::now()->format('dmY');
    }

    private function line(Carbon $date, int $diary, int $entryNumber, string $accountCode, string $concept, string $debitOrCredit, float $amount): string
    {
        return '10'
            .$date->format('dmY')                       // 8
            .$this->padLeftNum($diary, 2)               // 2
            .$this->padLeftNum($entryNumber, 7)         // 7
            .$this->padRight($accountCode, 10)          // 10
            .$this->padRight($concept, 40)              // 40
            .$debitOrCredit                              // 1
            .$this->amount($amount);                     // 14 (12 enteros + 2 decimales)
    }

    private function footer(int $totalRecords): string
    {
        return '99'.$this->padLeftNum($totalRecords, 7);
    }

    /** Importe en céntimos, sin separador, 14 dígitos con ceros a la izquierda. */
    private function amount(float $value): string
    {
        return $this->padLeftNum((int) round($value * 100), 14);
    }

    private function padRight(string $value, int $length): string
    {
        return mb_substr(str_pad($value, $length), 0, $length);
    }

    private function padLeftNum(int $value, int $length): string
    {
        return substr(str_pad((string) $value, $length, '0', STR_PAD_LEFT), -$length);
    }

    private function nif(?string $companyId): string
    {
        $company = $companyId !== null
            ? Company::find($companyId)
            : Company::query()->orderBy('created_at')->first();

        return (string) ($company?->cif ?? '');
    }
}
