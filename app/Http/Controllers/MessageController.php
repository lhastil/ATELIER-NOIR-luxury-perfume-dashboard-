<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The messages inbox. Messages are created by other systems; the panel lists
 * them, shows them, changes their read status and deletes them.
 */
class MessageController extends Controller
{
    public function index(Request $request): View
    {
        // A malformed query such as ?search[]=x counts as no search.
        $search = is_string($request->query('search')) ? trim($request->query('search')) : '';
        $status = in_array($request->query('status'), Message::STATUSES, true) ? $request->query('status') : null;

        $messages = Message::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            }))
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('messages.index', [
            'messages' => $messages,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function show(Message $message): View
    {
        // Opening an unread message marks it as read.
        if ($message->read_at === null) {
            $message->read_at = now();
            $message->save();
        }

        $message->load('customer');

        return view('messages.show', ['message' => $message]);
    }

    public function updateStatus(Request $request, Message $message): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Message::STATUSES)],
        ]);

        if ($validated['status'] === 'unread') {
            $message->read_at = null;
            $message->save();

            // Back to the inbox: reopening the message would mark it as read again.
            return redirect()->route('messages.index')->with('status', "Message \"{$message->subject}\" was marked as unread.");
        }

        $message->read_at ??= now();
        $message->save();

        return redirect()->route('messages.show', $message)->with('status', "Message \"{$message->subject}\" was marked as read.");
    }

    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('messages.index')->with('status', "Message \"{$message->subject}\" was deleted.");
    }
}
