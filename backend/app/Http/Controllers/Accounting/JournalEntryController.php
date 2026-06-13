<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Asientos contables (módulo contabilidad). El cuadre se valida en AccountingService.
 */
class JournalEntryController extends Controller
{
    public function __construct(private readonly AccountingService $service) {}

    public function index(Request $request): JsonResponse
    {
        $entries = JournalEntry::query()
            ->when($request->filled('year'), fn ($q) => $q->whereYear('date', $request->integer('year')))
            ->when($request->filled('entity_id'), fn ($q) => $q->where('entity_id', $request->string('entity_id')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->string('company_id')))
            ->withSum('lines as total', 'debit')
            ->with('lines.account:id,code,name')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($entries);
    }

    public function show(JournalEntry $journalEntry): JsonResponse
    {
        return response()->json(['data' => $journalEntry->load('lines.account:id,code,name')]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $entry = $this->service->createEntry($data, $data['lines'], $request->user()?->id);

        return response()->json(['data' => $entry], 201);
    }

    public function update(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $data = $this->validateData($request);
        $entry = $this->service->updateEntry($journalEntry, $data, $data['lines']);

        return response()->json(['data' => $entry]);
    }

    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        $journalEntry->delete();

        return response()->json(['message' => 'Asiento eliminado.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'entity_id' => ['nullable', 'uuid', 'exists:entities,id'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
