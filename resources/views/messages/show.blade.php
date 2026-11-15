<x-layouts.admin :title="$message->subject" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Messages', 'url' => route('messages.index')],
    ['label' => $message->subject],
]">
    <x-page-header :eyebrow="'Received '.$message->created_at->isoFormat('D MMMM YYYY, HH:mm')" :title="$message->subject">
        <x-slot:actions>
            <form method="POST" action="{{ route('messages.destroy', $message) }}" data-confirm="Delete the message &quot;{{ $message->subject }}&quot;? This cannot be undone.">
                @csrf
                @method('DELETE')
                <button type="submit" class="button button--danger">
                    <x-icon name="trash-2" :size="16" />
                    Delete
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="detail-layout">
        <x-card title="Message">
            {{-- Escaped like all output; line breaks are kept by the stylesheet. --}}
            <p class="message-body">{{ $message->body }}</p>
        </x-card>

        <div class="detail-layout__aside">
            <x-card title="Status">
                <form class="status-form" method="POST" action="{{ route('messages.status.update', $message) }}">
                    @csrf
                    @method('PATCH')

                    <p>Current status <x-badge :tone="$message->read_at ? 'neutral' : 'success'">{{ $message->read_at ? 'Read' : 'Unread' }}</x-badge></p>

                    @if ($message->read_at)
                        <input type="hidden" name="status" value="unread">
                        <button type="submit" class="button button--secondary">Mark as unread</button>
                    @else
                        <input type="hidden" name="status" value="read">
                        <button type="submit" class="button button--secondary">Mark as read</button>
                    @endif
                </form>
            </x-card>

            <x-card title="Sender">
                <dl class="detail-list">
                    <div>
                        <dt>Name</dt>
                        <dd>
                            @if ($message->customer && auth()->user()->can('access-customers'))
                                <a href="{{ route('customers.show', $message->customer) }}">{{ $message->name }}</a>
                            @else
                                {{ $message->name }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd>
                    </div>
                    <div>
                        <dt>Received</dt>
                        <dd>{{ $message->created_at->isoFormat('D MMMM YYYY, HH:mm') }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.admin>
