<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Events\ActivityFeedUpdated;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()->team_id;
        $activities = ActivityLog::forTeam($teamId)->with('user:id,name')->latest()->paginate(30);
        return response()->json($activities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string', 'subject_type' => 'required|string',
            'subject_id' => 'nullable|integer', 'description' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $activity = ActivityLog::create([...$validated, 'team_id' => $user->team_id, 'user_id' => $user->id]);

        ActivityFeedUpdated::dispatch($user->team_id, $user->name, $validated['action'], $validated['description']);
        return response()->json(['activity' => $activity], 201);
    }
}
