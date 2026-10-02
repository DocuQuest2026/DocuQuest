<?php

use App\Enums\RequestStatus;
use App\Models\DocumentRelease;
use App\Models\RecordRequest;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\Paginator as SimplePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    /**
     * Statuses shown as their own filter tab, alongside "All".
     *
     * @var array<int, RequestStatus>
     */
    private const FILTERABLE_STATUSES = [
        RequestStatus::Pending,
        RequestStatus::Approved,
        RequestStatus::Released,
        RequestStatus::Rejected,
    ];

    private const LIST_CACHE_SECONDS = 300;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    /**
     * Month the requests were submitted in, as "Y-m" from the month picker. It opens on the
     * current month unless a month is given in the address.
     */
    #[Url(except: '')]
    public string $month = '';

    /**
     * Optional exact day of the month the requests were submitted on, as a two-digit day
     * ('' for every day of the month).
     */
    #[Url(except: '')]
    public string $day = '';

    public function mount(): void
    {
        $this->useCurrentMonthUnlessSelectable();
        $this->clearDayUnlessInMonth();
        $this->refreshBadges();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->useCurrentMonthUnlessSelectable();
        $this->clearDayUnlessInMonth();
        $this->refreshBadges();
        $this->resetPage();
    }

    public function updatedDay(): void
    {
        $this->clearDayUnlessInMonth();
        $this->refreshBadges();
        $this->resetPage();
    }

    /**
     * The day numbers the day picker offers: every day of the selected month.
     *
     * @return array<int, Carbon>
     */
    #[Computed]
    public function daysOfMonth(): array
    {
        $first = Carbon::createFromFormat('!Y-m', $this->month);

        return array_map(fn (int $day): Carbon => $first->copy()->day($day), range(1, $first->daysInMonth));
    }

    /**
     * A day that doesn't exist in the selected month (such as 31 after switching to February)
     * is dropped, leaving every day of the month.
     */
    protected function clearDayUnlessInMonth(): void
    {
        $isInMonth = preg_match('/^\d{2}$/', $this->day)
            && (int) $this->day >= 1
            && (int) $this->day <= Carbon::createFromFormat('!Y-m', $this->month)->daysInMonth;

        if (! $isInMonth) {
            $this->day = '';
        }
    }

    /**
     * An empty, malformed or too-early month (such as a cleared picker, or a year before the
     * system was in use) falls back to the current month.
     */
    protected function useCurrentMonthUnlessSelectable(): void
    {
        $isSelectable = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)
            && $this->month >= config('school.first_request_month');

        if (! $isSelectable) {
            $this->month = $this->currentMonth();
        }
    }

    protected function currentMonth(): string
    {
        return now()->format('Y-m');
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    /**
     * @return array<int, RequestStatus>
     */
    #[Computed]
    public function filterableStatuses(): array
    {
        return self::FILTERABLE_STATUSES;
    }

    /**
     * Counts for the notification badges on the pending, approved and released tabs, for the
     * selected month or day only. The tab bar isn't re-rendered by the server (so the instant
     * highlight can't be overwritten), so the badges read this value from the browser instead.
     *
     * @var array<string, int>
     */
    #[Locked]
    public array $badges = [];

    protected function refreshBadges(): void
    {
        Gate::authorize('viewAny', RecordRequest::class);

        $this->badges = RecordRequest::badgeCounts($this->month, $this->day);
    }

    /**
     * The status tab in effect, or null when showing "All" (an unknown or non-filterable
     * status falls back to "All").
     */
    #[Computed]
    public function statusFilter(): ?RequestStatus
    {
        $status = RequestStatus::tryFrom($this->status);

        return in_array($status, self::FILTERABLE_STATUSES, true) ? $status : null;
    }

    public function showingArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function showingClaimed(): bool
    {
        return $this->status === 'claimed';
    }

    /**
     * Requests in first-come, first-served order (oldest at the top) for the active tab,
     * narrowed by the search. "Archived" shows soft-deleted requests, and "Claimed" shows
     * released documents that have actually been picked up.
     *
     * The release records are loaded only for the released rows on the page, so a page with no
     * released documents skips that database round trip entirely.
     *
     * @return Paginator<int, RecordRequest>
     */
    #[Computed]
    public function recordRequests(): Paginator
    {
        Gate::authorize('viewAny', RecordRequest::class);

        $load = function (): Paginator {
            $requests = $this->paginatedRequests();

            $requests->getCollection()
                ->filter(fn (RecordRequest $recordRequest): bool => $recordRequest->status === RequestStatus::Released)
                ->load('release');

            return $requests;
        };

        // Typed searches are one-off, so only the tabs, months, days and pages are cached.
        // The key carries the cache version, so any request or release change retires every
        // cached list at once.
        if (trim($this->search) !== '') {
            return $load();
        }

        $key = implode('.', [
            'request-list.rows',
            RecordRequest::cacheVersion(),
            $this->activeTab() ?: 'all',
            $this->month,
            $this->day ?: 'any',
            $this->getPage(),
        ]);

        // The cache only stores plain data (it refuses to unserialize models), so keep the raw
        // row attributes and rebuild the models and the page from them.
        $cached = Cache::remember($key, self::LIST_CACHE_SECONDS, function () use ($load): array {
            $requests = $load();

            return [
                'has_more' => $requests->hasMorePages(),
                'rows' => $requests->getCollection()->map(fn (RecordRequest $recordRequest): array => [
                    'attributes' => $recordRequest->getAttributes(),
                    'release_loaded' => $recordRequest->relationLoaded('release'),
                    'release' => $recordRequest->release?->getAttributes(),
                ])->all(),
            ];
        });

        $connection = (new RecordRequest)->getConnection()->getName();

        $models = (new Collection($cached['rows']))->map(function (array $row) use ($connection): RecordRequest {
            $recordRequest = (new RecordRequest)->newFromBuilder($row['attributes'], $connection);

            if ($row['release_loaded']) {
                $recordRequest->setRelation(
                    'release',
                    $row['release'] === null ? null : (new DocumentRelease)->newFromBuilder($row['release'], $connection)
                );
            }

            return $recordRequest;
        });

        return (new SimplePaginator($models, 15, $this->getPage(), [
            'path' => SimplePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]))->hasMorePagesWhen($cached['has_more']);
    }

    /**
     * @return Paginator<int, RecordRequest>
     */
    protected function paginatedRequests(): Paginator
    {
        $search = trim($this->search);

        if ($this->showingArchived()) {
            return RecordRequest::onlyTrashed()
                ->search($search)
                ->submittedIn($this->month, $this->day)
                ->latest('deleted_at')
                ->simplePaginate(15);
        }

        if ($this->showingClaimed()) {
            return RecordRequest::where('status', RequestStatus::Released)
                ->search($search)
                ->submittedIn($this->month, $this->day)
                ->whereHas('release', fn ($query) => $query->whereNotNull('claimed_at'))
                ->oldest()
                ->orderBy('id')
                ->simplePaginate(15);
        }

        $statusFilter = $this->statusFilter;

        return RecordRequest::query()
            ->search($search)
            ->submittedIn($this->month, $this->day)
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            // Once claimed, a request moves out of the "Released" tab and into "Claimed"
            // instead of sitting in both, so the tab only shows documents still awaiting pickup.
            ->when(
                $statusFilter === RequestStatus::Released,
                fn ($query) => $query->whereDoesntHave('release', fn ($q) => $q->whereNotNull('claimed_at'))
            )
            ->oldest()
            ->orderBy('id')
            ->simplePaginate(15);
    }

    /**
     * The tab to highlight: a status value, "claimed", "archived", or '' for "All".
     */
    public function activeTab(): string
    {
        return match (true) {
            $this->showingArchived() => 'archived',
            $this->showingClaimed() => 'claimed',
            default => $this->statusFilter?->value ?? '',
        };
    }
};
?>

<div
    class="space-y-4"
    x-data="{
        tab: @js($this->activeTab()),
        select(tab) {
            this.tab = tab;
            this.$wire.setStatus(tab);
        },
        tabClass(tab) {
            return this.tab === tab ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50';
        },
        rowVisible(row) {
            const words = this.$wire.search.trim().toLowerCase().split(/\s+/).filter(Boolean);

            if (! words.every((word) => row.search.includes(word))) {
                return false;
            }

            if (this.$wire.month !== '' && row.month !== this.$wire.month) {
                return false;
            }

            if (this.$wire.day !== '' && row.day !== this.$wire.day) {
                return false;
            }

            if (['pending', 'approved', 'rejected'].includes(this.tab)) {
                return row.status === this.tab;
            }

            if (this.tab === 'released') {
                return row.status === 'released' && row.claimed === '0';
            }

            return true;
        },
    }"
>
    <div class="flex flex-wrap gap-2">
        <x-text-input
            type="search"
            wire:model.live.debounce.250ms="search"
            placeholder="{{ __('Search by name, reference no. or student no.') }}"
            aria-label="{{ __('Search requests') }}"
            class="block w-full sm:max-w-md"
            autocomplete="off"
        />

        @if ($this->search !== '')
            <button
                type="button"
                wire:click="$set('search', '')"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm text-gray-600 shadow-sm hover:bg-gray-50"
            >{{ __('Clear') }}</button>
        @endif

        <x-text-input
            type="month"
            min="{{ config('school.first_request_month') }}"
            wire:model.live="month"
            aria-label="{{ __('Filter by month submitted') }}"
            title="{{ __('Show only requests submitted in this month') }}"
            class="block"
        />

        <x-select-input
            wire:model.live="day"
            aria-label="{{ __('Filter by day submitted') }}"
            title="{{ __('Show only requests submitted on this day') }}"
        >
            <option value="">{{ __('All days') }}</option>
            @foreach ($this->daysOfMonth as $date)
                <option value="{{ $date->format('d') }}" wire:key="day-{{ $date->format('Ymd') }}">{{ $date->format('j · D') }}</option>
            @endforeach
        </x-select-input>
    </div>

    <div class="flex flex-wrap gap-2" wire:ignore>
        <button
            type="button"
            x-on:click="select('')"
            :class="tabClass('')"
            class="rounded-md px-3 py-1.5 text-sm font-medium shadow-sm"
        >
            {{ __('All') }}
        </button>
        @foreach ($this->filterableStatuses as $filterableStatus)
            <button
                type="button"
                x-on:click="select('{{ $filterableStatus->value }}')"
                :class="tabClass('{{ $filterableStatus->value }}')"
                class="rounded-md px-3 py-1.5 text-sm font-medium shadow-sm"
            >
                {{ $filterableStatus->label() }}
                @if (in_array($filterableStatus, [RequestStatus::Pending, RequestStatus::Approved, RequestStatus::Released], true))
                    <span
                        x-show="($wire.badges['{{ $filterableStatus->value }}'] ?? 0) > 0"
                        x-cloak
                        x-text="$wire.badges['{{ $filterableStatus->value }}'] > 9 ? '9+' : $wire.badges['{{ $filterableStatus->value }}']"
                        class="ms-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white"
                    ></span>
                @endif
            </button>

            @if ($filterableStatus === RequestStatus::Released)
                <button
                    type="button"
                    x-on:click="select('claimed')"
                    :class="tabClass('claimed')"
                    class="rounded-md px-3 py-1.5 text-sm font-medium shadow-sm"
                >
                    {{ __('Claimed') }}
                </button>
            @endif
        @endforeach

        <button
            type="button"
            x-on:click="select('archived')"
            :class="tabClass('archived')"
            class="rounded-md px-3 py-1.5 text-sm font-medium shadow-sm"
        >
            {{ __('Archived') }}
        </button>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto transition-opacity" wire:loading.class="opacity-50" wire:target="search,month,day,setStatus,gotoPage,nextPage,previousPage">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Reference') }}</th>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Student') }}</th>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Document') }}</th>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Copies') }}</th>
                    <th scope="col" class="px-4 py-3 font-medium">
                        {{ $this->showingArchived() ? __('Archived') : ($this->showingClaimed() ? __('Claimed') : __('Submitted')) }}
                    </th>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php
                    $statusColors = [
                        RequestStatus::Pending->value => 'bg-yellow-100 text-yellow-800',
                        RequestStatus::Approved->value => 'bg-blue-100 text-blue-800',
                        RequestStatus::Released->value => 'bg-green-100 text-green-800',
                        RequestStatus::Rejected->value => 'bg-red-100 text-red-800',
                        RequestStatus::CancellationRequested->value => 'bg-orange-100 text-orange-800',
                        RequestStatus::Cancelled->value => 'bg-gray-200 text-gray-700',
                    ];
                @endphp
                @forelse ($this->recordRequests as $recordRequest)
                    <tr
                        wire:key="request-{{ $recordRequest->id }}"
                        data-search="{{ mb_strtolower(implode(' ', [$recordRequest->reference_no, $recordRequest->student_no, $recordRequest->fullName()])) }}"
                        data-month="{{ $recordRequest->created_at->format('Y-m') }}"
                        data-day="{{ $recordRequest->created_at->format('d') }}"
                        data-status="{{ $recordRequest->status->value }}"
                        data-claimed="{{ $recordRequest->relationLoaded('release') && $recordRequest->release?->claimed_at ? 1 : 0 }}"
                        x-show="rowVisible($el.dataset)"
                    >
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $recordRequest->reference_no }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $recordRequest->fullName() }}
                            <span class="block text-xs text-gray-500">{{ $recordRequest->student_no }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $recordRequest->document_type->label() }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $recordRequest->copies }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @php
                                $dateColumn = $this->showingArchived()
                                    ? $recordRequest->deleted_at
                                    : ($this->showingClaimed() ? $recordRequest->release->claimed_at : $recordRequest->created_at);
                            @endphp
                            {{ $dateColumn->format('M j, Y g:i A') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$recordRequest->status->value] }}">{{ $recordRequest->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                            <a wire:navigate.hover href="{{ route('requests.show', $recordRequest) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">{{ __('View') }}</a>

                            @can('claim', $recordRequest)
                                <button
                                    type="button"
                                    x-data=""
                                    x-on:click.prevent="$dispatch('open-modal', 'confirm-claim-{{ $recordRequest->id }}')"
                                    class="inline-flex items-center rounded-md bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700"
                                >{{ __('Claim') }}</button>

                                <x-modal name="confirm-claim-{{ $recordRequest->id }}" focusable>
                                    <form method="POST" action="{{ route('requests.claim', $recordRequest) }}" class="p-6">
                                        @csrf

                                        <h2 class="text-lg font-medium text-gray-900">{{ __('Mark as claimed') }}</h2>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ __('This defaults to right now, but you can set a different date and time if the pickup already happened.') }}
                                        </p>

                                        <div class="mt-4">
                                            <x-input-label for="claimed_at_{{ $recordRequest->id }}" :value="__('Claimed at')" />
                                            <x-text-input
                                                id="claimed_at_{{ $recordRequest->id }}"
                                                name="claimed_at"
                                                type="datetime-local"
                                                class="mt-1 block w-full"
                                                value="{{ now()->format('Y-m-d\TH:i') }}"
                                                max="{{ now()->format('Y-m-d\TH:i') }}"
                                                required
                                            />
                                        </div>

                                        <div class="mt-6 flex justify-end">
                                            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                                {{ __('Cancel') }}
                                            </x-secondary-button>

                                            <x-primary-button class="ms-3">
                                                {{ __('Confirm claim') }}
                                            </x-primary-button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endcan

                            @can('restore', $recordRequest)
                                <form method="POST" action="{{ route('requests.restore', $recordRequest) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">{{ __('Restore') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            @if (trim($this->search) !== '')
                                {{ __('No requests match your search.') }}
                            @elseif ($this->day !== '')
                                {{ __('No requests were submitted on that day.') }}
                            @elseif ($this->month !== '')
                                {{ __('No requests were submitted in that month.') }}
                            @elseif ($this->showingArchived())
                                {{ __('No archived requests.') }}
                            @elseif ($this->showingClaimed())
                                {{ __('No claimed documents yet.') }}
                            @else
                                {{ __('No requests have been submitted yet.') }}
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->recordRequests->links() }}
</div>
