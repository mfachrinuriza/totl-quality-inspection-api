<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QualityInspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    /** GET /api/inspections?since=ISO8601 — daftar transaksi (row digabung per TransactionId). */
    public function index(Request $r): JsonResponse
    {
        $q = QualityInspection::with(['pictures', 'pinpoints'])->where('Status', 1);
        if ($r->filled('since')) {
            $q->where('UpdatedAt', '>', $r->date('since'));
        }
        $rows = $q->orderByDesc('CreatedAt')->orderBy('ID')->get();

        return $this->successResponse(
            'Data inspeksi berhasil diambil.',
            $rows->groupBy('TransactionId')->map(fn ($g) => $this->format($g))->values()
        );
    }

    /** GET /api/inspections/{transactionId} */
    public function show(string $transactionId): JsonResponse
    {
        $rows = $this->load($transactionId);
        return $rows->isEmpty()
            ? $this->errorResponse('Data inspeksi tidak ditemukan.', null, 404)
            : $this->successResponse('Data inspeksi berhasil diambil.', $this->format($rows));
    }

    /** POST /api/inspections/submit — tiap temuan disimpan sebagai row terpisah. Idempotent. */
    public function submit(Request $r): JsonResponse
    {
        $data = $r->validate([
            'transactionId' => 'required|uuid',
            'tower' => 'nullable|string|max:100',
            'lantai' => 'nullable|string|max:100',
            'zona' => 'nullable|string|max:100',
            'drawingUrl' => 'nullable|string|max:1000',
            'picTotal' => 'nullable|string|max:100',
            'mk' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:100',
            'findings' => 'required|array|min:1',
            'findings.*.clientId' => 'required|uuid',
            'findings.*.ketidaksesuaian' => 'nullable|string|max:1000',
            'findings.*.descTemuan' => 'nullable|string',
            'findings.*.pictures' => 'nullable|array',
            'findings.*.pictures.*.pictureUrl' => 'required|string|max:1000',
            'findings.*.pictures.*.keterangan' => 'nullable|string|max:2000',
            'findings.*.pinpoints' => 'nullable|array',
            'findings.*.pinpoints.*.drawingUrl' => 'required|string|max:1000',
            'findings.*.pinpoints.*.keterangan' => 'nullable|string|max:5000',
            'findings.*.pinpoints.*.pinpointX' => 'required|numeric',
            'findings.*.pinpoints.*.pinpointY' => 'required|numeric',
            'findings.*.pinpoints.*.pageNumber' => 'required|integer|min:1',
        ]);

        // Retry sync dengan TransactionId yang sama tidak membuat data ganda.
        $existing = $this->load($data['transactionId']);
        if ($existing->isNotEmpty()) {
            return $this->successResponse('Data inspeksi sudah tersedia.', $this->format($existing));
        }

        DB::transaction(function () use ($data) {
            $now = now();
            foreach ($data['findings'] as $f) {
                $row = QualityInspection::create([
                    'TransactionId' => $data['transactionId'],
                    'ClientId' => $f['clientId'],
                    'Tower' => $data['tower'] ?? null,
                    'Lantai' => $data['lantai'] ?? null,
                    'Zona' => $data['zona'] ?? null,
                    'DrawingUrl' => $data['drawingUrl'] ?? null,
                    'PICTotal' => $data['picTotal'] ?? null,
                    'MK' => $data['mk'] ?? null,
                    'Vendor' => $data['vendor'] ?? null,
                    'Ketidaksesuaian' => $f['ketidaksesuaian'] ?? null,
                    'DescTemuan' => $f['descTemuan'] ?? null,
                    'Status' => 1,
                    'CreatedAt' => $now,
                    'UpdatedAt' => $now,
                ]);
                foreach ($f['pictures'] ?? [] as $p) {
                    $row->pictures()->create([
                        'PictureUrl' => $p['pictureUrl'], 'Keterangan' => $p['keterangan'] ?? null, 'Status' => 1,
                    ]);
                }
                foreach ($f['pinpoints'] ?? [] as $p) {
                    $row->pinpoints()->create([
                        'DrawingUrl' => $p['drawingUrl'], 'Keterangan' => $p['keterangan'] ?? null,
                        'PinpointX' => $p['pinpointX'], 'PinpointY' => $p['pinpointY'],
                        'PageNumber' => $p['pageNumber'], 'Status' => 1,
                    ]);
                }
            }
        });

        return $this->successResponse(
            'Data inspeksi berhasil disimpan.',
            $this->format($this->load($data['transactionId'])),
            201
        );
    }

    /** PUT /api/inspections/{transactionId} — update nama PIC & Vendor untuk semua row. */
    public function update(Request $r, string $transactionId): JsonResponse
    {
        $data = $r->validate([
            'picTotal' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:100',
        ]);

        $n = QualityInspection::where('TransactionId', $transactionId)->where('Status', 1)
            ->update(['PICTotal' => $data['picTotal'] ?? null, 'Vendor' => $data['vendor'] ?? null, 'UpdatedAt' => now()]);

        return $n === 0
            ? $this->errorResponse('Data inspeksi tidak ditemukan.', null, 404)
            : $this->successResponse('Data inspeksi berhasil diperbarui.', $this->format($this->load($transactionId)));
    }

    /** DELETE /api/inspections/{transactionId} — soft delete (Status = 0). */
    public function destroy(string $transactionId): JsonResponse
    {
        $ids = QualityInspection::where('TransactionId', $transactionId)->where('Status', 1)->pluck('ID');
        if ($ids->isEmpty()) {
            return $this->errorResponse('Data inspeksi tidak ditemukan.', null, 404);
        }
        DB::transaction(function () use ($ids, $transactionId) {
            DB::table('Td_QualityInspection_Picture')->whereIn('ThQualityInspectionID', $ids)->update(['Status' => 0]);
            DB::table('Td_QualityInspection_Pinpoint')->whereIn('ThQualityInspectionID', $ids)->update(['Status' => 0]);
            QualityInspection::where('TransactionId', $transactionId)->update(['Status' => 0, 'UpdatedAt' => now()]);
        });
        return $this->successResponse('Data inspeksi berhasil dihapus.');
    }

    private function load(string $transactionId): Collection
    {
        return QualityInspection::with(['pictures', 'pinpoints'])
            ->where('TransactionId', $transactionId)->where('Status', 1)->orderBy('ID')->get();
    }

    private function format(Collection $rows): array
    {
        $h = $rows->first();
        return [
            'transactionId' => $h->TransactionId,
            'tower' => $h->Tower, 'lantai' => $h->Lantai, 'zona' => $h->Zona,
            'drawingUrl' => $h->DrawingUrl, 'picTotal' => $h->PICTotal, 'mk' => $h->MK, 'vendor' => $h->Vendor,
            'createdAt' => $rows->min('CreatedAt'), 'updatedAt' => $rows->max('UpdatedAt'),
            'findings' => $rows->map(fn ($r) => [
                'id' => $r->ID, 'clientId' => $r->ClientId,
                'ketidaksesuaian' => $r->Ketidaksesuaian, 'descTemuan' => $r->DescTemuan,
                'pictures' => $r->pictures->map(fn ($p) => [
                    'pictureUrl' => $p->PictureUrl, 'keterangan' => $p->Keterangan,
                ])->values(),
                'pinpoints' => $r->pinpoints->map(fn ($p) => [
                    'drawingUrl' => $p->DrawingUrl, 'keterangan' => $p->Keterangan,
                    'pinpointX' => (float) $p->PinpointX, 'pinpointY' => (float) $p->PinpointY,
                    'pageNumber' => $p->PageNumber,
                ])->values(),
            ])->values(),
        ];
    }
}
