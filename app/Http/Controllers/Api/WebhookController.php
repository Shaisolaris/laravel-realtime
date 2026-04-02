<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Models\Webhook;
use App\Jobs\DispatchWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $webhooks = Webhook::where('team_id', $request->user()->team_id)
            ->withCount('deliveries')
            ->latest()
            ->get();
        return response()->json(['webhooks' => $webhooks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ]);

        $webhook = Webhook::create([
            'team_id' => $request->user()->team_id,
            'url' => $validated['url'],
            'secret' => Str::random(40),
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return response()->json(['webhook' => $webhook, 'secret' => $webhook->secret], 201);
    }

    public function deliveries(int $id, Request $request): JsonResponse
    {
        $webhook = Webhook::where('team_id', $request->user()->team_id)->findOrFail($id);
        $deliveries = $webhook->deliveries()->latest()->paginate(20);
        return response()->json($deliveries);
    }

    public function toggle(int $id, Request $request): JsonResponse
    {
        $webhook = Webhook::where('team_id', $request->user()->team_id)->findOrFail($id);
        $webhook->update(['is_active' => !$webhook->is_active]);
        return response()->json(['webhook' => $webhook]);
    }

    public function test(int $id, Request $request): JsonResponse
    {
        $webhook = Webhook::where('team_id', $request->user()->team_id)->findOrFail($id);
        DispatchWebhookJob::dispatch($webhook->team_id, 'webhook.test', ['message' => 'Test delivery', 'timestamp' => now()->toIso8601String()]);
        return response()->json(['status' => 'Test webhook queued']);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        Webhook::where('team_id', $request->user()->team_id)->findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
