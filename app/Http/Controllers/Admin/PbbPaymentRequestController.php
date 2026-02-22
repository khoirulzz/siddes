<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PbbPaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PbbPaymentRequestController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $itemsQuery = PbbPaymentRequest::query();

        if ($keyword !== '') {
            $keywordLike = '%' . $keyword . '%';
            $upperKeyword = Str::upper($keyword);

            $itemsQuery->where(function ($query) use ($keywordLike, $upperKeyword): void {
                $query->where('applicant_name', 'like', $keywordLike)
                    ->orWhere('phone', 'like', $keywordLike)
                    ->orWhere('nop', 'like', $keywordLike);

                if ($this->hasTicketCodeColumn()) {
                    $query->orWhere('ticket_code', 'like', '%' . $upperKeyword . '%');
                }
            });
        }

        if ($status !== '') {
            $itemsQuery->where('status', $status);
        }

        $items = $itemsQuery
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.services.pbb.index', [
            'items' => $items,
            'filters' => [
                'q' => $keyword,
                'status' => $status,
            ],
            'statusOptions' => $this->statusOptions(),
            'stats' => [
                'total' => PbbPaymentRequest::query()->count(),
                'diajukan' => PbbPaymentRequest::query()->where('status', 'Diajukan')->count(),
                'proses' => PbbPaymentRequest::query()->where('status', 'Diproses')->count(),
                'selesai' => PbbPaymentRequest::query()->where('status', 'Selesai')->count(),
            ],
        ]);
    }

    public function show(PbbPaymentRequest $pbbPaymentRequest)
    {
        return view('dashboard.services.pbb.show', [
            'item' => $pbbPaymentRequest,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, PbbPaymentRequest $pbbPaymentRequest)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in($this->statusOptions())],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $pbbPaymentRequest->update($data);

        return redirect()->route('dashboard.pbb-payment-requests.index')->with('success', 'Status pengajuan PBB berhasil diperbarui.');
    }

    public function destroy(PbbPaymentRequest $pbbPaymentRequest)
    {
        $pbbPaymentRequest->delete();

        return redirect()->route('dashboard.pbb-payment-requests.index')->with('success', 'Data pengajuan PBB berhasil dihapus.');
    }

    private function statusOptions(): array
    {
        return ['Diajukan', 'Diproses', 'Selesai', 'Ditolak'];
    }

    private function hasTicketCodeColumn(): bool
    {
        static $hasTicketCodeColumn = null;

        if ($hasTicketCodeColumn === null) {
            $hasTicketCodeColumn = Schema::hasColumn('pbb_payment_requests', 'ticket_code');
        }

        return $hasTicketCodeColumn;
    }
}
