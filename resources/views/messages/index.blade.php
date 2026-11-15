<x-layouts.admin title="Messages" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Messages']]">
    <x-page-header eyebrow="Clientele" title="Messages" description="Messages sent to the house, newest first. Unread messages are shown in bold." />

    @php($filtering = $filters['search'] !== '' || $filters['status'])

    <form class="filters" method="GET" action="{{ route('messages.index') }}" role="search" aria-label="Filter messages">
        <div class="field filters__search">
            <label class="field__label" for="search">Search</label>
            <input id="search" class="field__input" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Sender name, email or subject">
        </div>

        <div class="field">
            <label class="field__label" for="status">Status</label>
            <select id="status" class="field__input field__select" name="status">
                <option value="">Read and unread</option>
                <option value="unread" @selected($filters['status'] === 'unread')>Unread</option>
                <option value="read" @selected($filters['status'] === 'read')>Read</option>
            </select>
        </div>

        <div class="filters__actions">
            <button type="submit" class="button button--secondary">Apply</button>
            @if ($filtering)
                <a class="button button--ghost" href="{{ route('messages.index') }}">Reset</a>
            @endif
        </div>
    </form>

    <x-card title="Inbox" :description="$messages->total().' '.Str::plural('message', $messages->total()).($filtering ? ($messages->total() === 1 ? ' matches' : ' match').' the filters' : '')">
        <x-table caption="Messages" :columns="[
            'From',
            'Subject',
            'Received',
            'Status',
            ['label' => 'Actions', 'hidden' => true],
        ]">
            @foreach ($messages as $message)
                <tr @class(['message-row--unread' => ! $message->read_at])>
                    <td>
                        {{ $message->name }}
                        <span class="table-meta">{{ $message->email }}</span>
                    </td>
                    <td><a class="table-link" href="{{ route('messages.show', $message) }}">{{ $message->subject }}</a></td>
                    <td>{{ $message->created_at->isoFormat('D MMM YYYY, HH:mm') }}</td>
                    <td><x-badge :tone="$message->read_at ? 'neutral' : 'success'">{{ $message->read_at ? 'Read' : 'Unread' }}</x-badge></td>
                    <td>
                        <div class="row-actions">
                            <form method="POST" action="{{ route('messages.destroy', $message) }}" data-confirm="Delete the message &quot;{{ $message->subject }}&quot;? This cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-button icon-button--danger" aria-label="Delete the message {{ $message->subject }}">
                                    <x-icon name="trash-2" :size="16" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach

            <x-slot:empty>
                @if ($messages->total() > 0)
                    <x-empty-state icon="mail" title="This page is empty">
                        <a href="{{ $messages->url(1) }}">Go to the first page</a>.
                    </x-empty-state>
                @elseif ($filtering)
                    <x-empty-state icon="search" title="No matching messages">
                        Try another search or <a href="{{ route('messages.index') }}">reset the filters</a>.
                    </x-empty-state>
                @else
                    <x-empty-state icon="mail" title="No messages yet">
                        Messages appear here once they are sent to the house.
                    </x-empty-state>
                @endif
            </x-slot:empty>
        </x-table>

        {{ $messages->links('pagination.admin') }}
    </x-card>
</x-layouts.admin>
