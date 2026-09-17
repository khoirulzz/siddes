<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessagingSubmission;
use App\Services\Messaging\MessagingException;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessagingController extends Controller
{
    public function __construct(private MessagingService $messaging) {}

    private function enabled(): void { abort_unless(config('messaging.enabled'), 404); }

    private function render(string $view, callable $load)
    {
        $this->enabled();
        try { return view('dashboard.messaging.'.$view, ['unavailable' => null, ...$load()]); }
        catch (MessagingException $error) { return response()->view('dashboard.messaging.unavailable', ['unavailable' => $error->getMessage()], 503); }
    }

    private function mutate(callable $operation, string $message)
    {
        $this->enabled();
        try { $operation(); return back()->with('success', $message); }
        catch (MessagingException $error) { return back()->withInput()->with('error', $error->getMessage()); }
    }

    public function overview()
    {
        return $this->render('overview', fn () => ['data' => $this->messaging->request('GET', 'dashboard'), 'pending' => MessagingSubmission::where('actor_id', auth()->id())->whereIn('status', ['PENDING', 'UNCERTAIN'])->latest()->limit(5)->get()]);
    }

    public function contacts(Request $request)
    {
        return $this->render('contacts', fn () => ['items' => $this->messaging->page('contacts', $request->only('page', 'search'))->paginator(), 'edit' => $request->filled('edit') ? $this->messaging->request('GET', 'contacts/'.$request->validate(['edit' => 'uuid'])['edit']) : null]);
    }

    public function saveContact(Request $request, ?string $id = null)
    {
        if ($request->has('isActive') && ! $request->has('fullName')) $data = $request->validate(['isActive' => 'required|boolean']);
        else $data = $request->validate(['fullName' => 'required|string|min:2|max:120', 'phone' => 'required|string|min:8|max:30', 'whatsappOptIn' => 'required|boolean']);
        foreach (['isActive', 'whatsappOptIn'] as $field) if (array_key_exists($field, $data)) $data[$field] = $request->boolean($field);
        return $this->mutate(fn () => $this->messaging->request($id ? 'PATCH' : 'POST', 'contacts'.($id ? '/'.$id : ''), $data), 'Kontak berhasil disimpan.');
    }

    public function importContacts(Request $request)
    {
        $this->enabled();
        $data = $request->validate(['rows' => 'required|array|min:1|max:1000', 'rows.*.rowNumber' => 'required|integer|min:1', 'rows.*.fullName' => 'required|string|max:500', 'rows.*.phone' => 'required|string|max:100', 'rows.*.whatsappOptIn' => 'required|boolean']);
        try { return response()->json($this->messaging->request('POST', 'contacts/import', $data), 201); }
        catch (MessagingException $error) { return response()->json(['message' => $error->getMessage()], $error->httpStatus); }
    }

    public function templates(Request $request)
    {
        return $this->render('templates', fn () => ['items' => $this->messaging->page('templates', $request->only('page', 'search'))->paginator(), 'edit' => $request->filled('edit') ? $this->messaging->request('GET', 'templates/'.$request->validate(['edit' => 'uuid'])['edit']) : null]);
    }

    public function saveTemplate(Request $request, ?string $id = null)
    {
        $data = $request->has('isActive') && ! $request->has('content') ? $request->validate(['isActive' => 'required|boolean']) : $request->validate(['name' => 'required|string|min:2|max:100', 'content' => 'required|string|max:4000']);
        if (array_key_exists('isActive', $data)) $data['isActive'] = $request->boolean('isActive');
        return $this->mutate(fn () => $this->messaging->request($id ? 'PATCH' : 'POST', 'templates'.($id ? '/'.$id : ''), $data), 'Template berhasil disimpan.');
    }

    public function campaigns(Request $request)
    {
        return $this->render('campaigns', fn () => ['items' => $this->messaging->page('campaigns', $request->only('page', 'search', 'status'))->paginator()]);
    }

    public function compose()
    {
        return $this->render('compose', fn () => ['settings' => $this->messaging->request('GET', 'campaigns/settings'), 'requestUuid' => old('request_uuid', (string) Str::uuid())]);
    }

    public function recipients(Request $request)
    {
        $this->enabled();
        $resource = $request->input('resource') === 'templates' ? 'templates' : 'contacts';
        try { return response()->json($this->messaging->request('GET', $resource, [...$request->only('page', 'search'), 'perPage' => 20, 'eligible' => 'true'])); }
        catch (MessagingException $error) { return response()->json(['message' => $error->getMessage()], $error->httpStatus); }
    }

    private function campaignPayload(Request $request): array
    {
        // JSON preview uses LF; native HTML textarea submission uses CRLF.
        // Canonicalize before validation, ledger hashing and backend forwarding.
        // Keep the backend's content/recipient snapshot validation intact.
        if (is_string($request->input('content'))) {
            $request->merge(['content' => str_replace(["\r\n", "\r"], "\n", $request->input('content'))]);
        }
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'content' => 'required|string|max:4000', 'templateId' => 'nullable|uuid', 'contactIds' => 'required|array|min:1|max:1000', 'contactIds.*' => 'required|uuid|distinct', 'batchSize' => 'required|integer|min:1|max:50', 'useBanner' => 'nullable|boolean', 'useInteractiveCta' => 'nullable|boolean']);
        $data['batchSize'] = (int) $data['batchSize'];
        $data['useBanner'] = $request->boolean('useBanner');
        $data['useInteractiveCta'] = $request->boolean('useInteractiveCta');
        if (empty($data['templateId'])) unset($data['templateId']);
        sort($data['contactIds']);
        return $data;
    }

    public function preview(Request $request)
    {
        $this->enabled();
        $payload = $this->campaignPayload($request);
        try { return response()->json($this->messaging->request('POST', 'campaigns/preview', $payload)); }
        catch (MessagingException $error) { return response()->json(['message' => $error->getMessage()], $error->httpStatus); }
    }

    public function createCampaign(Request $request)
    {
        $this->enabled();
        $payload = $this->campaignPayload($request);
        $validated = $request->validate(['request_uuid' => 'required|uuid', 'previewToken' => 'required|string']);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $ledger = MessagingSubmission::firstOrCreate(['request_uuid' => $validated['request_uuid']], ['actor_id' => auth()->id(), 'payload_hash' => $hash]);
        abort_unless((string) $ledger->actor_id === (string) auth()->id() && hash_equals($ledger->payload_hash, $hash), 409, 'Form ini sudah digunakan dengan isi berbeda. Tinjau kembali campaign.');
        if ($ledger->campaign_id) return redirect()->route('dashboard.messaging.campaigns.show', $ledger->campaign_id);
        try {
            if (! $ledger->wasRecentlyCreated) {
                try { $existing = $this->messaging->request('GET', 'campaigns/by-request/'.$ledger->request_uuid); }
                catch (MessagingException $error) { if ($error->httpStatus !== 404) throw $error; }
            }
            $campaign = $existing ?? $this->messaging->request('POST', 'campaigns', [...$payload, 'previewToken' => $validated['previewToken'], '_idempotency_key' => $ledger->request_uuid]);
            if (! Str::isUuid($campaign['id'] ?? '')) throw new MessagingException('Respons pembuatan draft belum dapat dipastikan.', 503, true);
            $ledger->update(['campaign_id' => $campaign['id'], 'status' => 'CONFIRMED']);
            return redirect()->route('dashboard.messaging.campaigns.show', $campaign['id'])->with('success', 'Draft berhasil dibuat. Tinjau lalu pilih Mulai pengiriman.');
        } catch (MessagingException $error) {
            $ledger->update(['status' => $error->uncertain ? 'UNCERTAIN' : 'REJECTED']);
            if (! $error->uncertain) $request->merge(['request_uuid' => (string) Str::uuid()]);
            return back()->withInput()->with('error', $error->uncertain ? 'Status pembuatan belum dapat dipastikan. Periksa status draft ini sebelum membuat campaign baru.' : $error->getMessage())->with('messaging_uncertain_key', $error->uncertain ? $ledger->request_uuid : null);
        }
    }

    public function reconcile(string $key)
    {
        $this->enabled();
        $ledger = MessagingSubmission::where('request_uuid', $key)->where('actor_id', auth()->id())->firstOrFail();
        try {
            $campaign = $this->messaging->request('GET', 'campaigns/by-request/'.$key);
            $ledger->update(['campaign_id' => $campaign['id'], 'status' => 'CONFIRMED']);
            return redirect()->route('dashboard.messaging.campaigns.show', $campaign['id']);
        } catch (MessagingException $error) {
            return back()->with('error', $error->httpStatus === 404 ? 'Draft belum ditemukan. Tinjau ulang lalu kirim kembali form yang sama; jangan membuat campaign kedua.' : $error->getMessage());
        }
    }

    public function campaignDetail(Request $request, string $id)
    {
        return $this->render('detail', fn () => ['campaign' => $this->messaging->request('GET', 'campaigns/'.$id), 'messages' => $this->messaging->page('messages', ['campaignId' => $id, ...$request->only('page', 'status')])->paginator()]);
    }

    public function campaignAction(string $id, string $action)
    {
        return $this->mutate(fn () => $this->messaging->request('POST', 'campaigns/'.$id.'/'.$action), 'Status campaign berhasil diperbarui.');
    }

    public function history(Request $request)
    {
        return $this->render('history', fn () => ['items' => $this->messaging->page('messages', $request->only('page', 'search', 'status', 'campaignId'))->paginator()]);
    }

    public function retry(string $id)
    {
        return $this->mutate(fn () => $this->messaging->request('POST', 'messages/'.$id.'/retry'), 'Pesan kembali masuk antrean.');
    }

    public function connection()
    {
        return $this->render('connection', fn () => ['state' => $this->messaging->request('GET', 'whatsapp/status', [], true)]);
    }

    public function connectionAction(string $action)
    {
        return $this->mutate(fn () => $this->messaging->request('POST', 'whatsapp/'.$action, [], true), 'Permintaan koneksi diproses.');
    }
}
