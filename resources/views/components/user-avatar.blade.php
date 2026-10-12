@props(['user'])

@php
    $initials = \Illuminate\Support\Str::of($user->name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->implode('');
    $photoUrl = $user->profilePhotoUrl();
@endphp

<span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 text-xs font-bold text-indigo-700']) }}>
    @if ($photoUrl)
        <img src="{{ $photoUrl }}" alt="" class="h-full w-full object-cover">
    @else
        {{ $initials }}
    @endif
</span>
