<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Events\UserPresenceUpdated;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PresenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate(['status' => 'required|in:online,away,offline']);
        $user = $request->user();
        $user->update(['presence_status' => $validated['status']]);

        if ($user->team_id) {
            UserPresenceUpdated::dispatch($user->team_id, $user->id, $user->name, $validated['status']);
        }

        return response()->json(['status' => $validated['status']]);
    }

    public function team(Request $request): JsonResponse
    {
        $members = User::where('team_id', $request->user()->team_id)
            ->select(['id', 'name', 'presence_status'])
            ->get();
        return response()->json(['members' => $members]);
    }
}
