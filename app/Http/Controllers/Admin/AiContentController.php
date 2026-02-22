<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\AiWriterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiContentController extends Controller
{
    public function __construct(private readonly AiWriterService $aiWriterService)
    {
    }

    public function generateNews(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'brief' => ['required', 'string', 'max:4000'],
            'tone' => ['nullable', Rule::in(['informatif', 'formal', 'netral', 'persuasif'])],
            'length' => ['nullable', Rule::in(['short', 'medium', 'long'])],
            'keywords' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->aiWriterService->generate('news', $payload, $request->user()?->id);

        return response()->json($result, $result['status']);
    }

    public function generateAnnouncement(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'brief' => ['required', 'string', 'max:3000'],
            'tone' => ['nullable', Rule::in(['informatif', 'formal', 'netral'])],
            'length' => ['nullable', Rule::in(['short', 'medium', 'long'])],
            'keywords' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->aiWriterService->generate('announcement', $payload, $request->user()?->id);

        return response()->json($result, $result['status']);
    }
}

