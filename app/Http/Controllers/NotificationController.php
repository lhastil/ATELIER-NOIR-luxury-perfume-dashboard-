<?php

namespace App\Http\Controllers;

use App\View\Composers\NotificationsComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Notifications are read from the orders, customers and messages tables (see
 * App\View\Composers\NotificationsComposer); only the read state is stored.
 */
class NotificationController extends Controller
{
    /**
     * Marks everything up to now as read for the signed-in administrator only.
     * The top bar script (resources/js/ui/notifications.js) asks for JSON and
     * updates the dropdown in place; without JavaScript the form returns to the page.
     */
    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->forceFill(['notifications_read_at' => now()])->save();

        // Notifications opened one at a time are now covered by notifications_read_at.
        DB::table('notification_reads')->where('user_id', $request->user()->id)->delete();

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => 0]);
        }

        return back();
    }

    /**
     * Marks one notification as read for the signed-in administrator only, when
     * they open it from the bell. The script then goes to the record's page; a
     * plain request is sent there directly.
     */
    public function read(Request $request, NotificationsComposer $notifications, string $type, int $id): JsonResponse|RedirectResponse
    {
        $source = NotificationsComposer::SOURCES[$type];
        $user = $request->user();

        // The same role rule as the bell and the section itself (Sellers cannot open customers).
        abort_unless($user->can('access-'.$source['section']), 403);

        $record = $source['model']::query()->findOrFail($id);

        // Opening it twice changes nothing.
        DB::table('notification_reads')->insertOrIgnore([
            'user_id' => $user->id,
            'type' => $type,
            'record_id' => $record->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => $notifications->unreadCount($user)]);
        }

        return redirect()->route($source['route'], $record);
    }
}
