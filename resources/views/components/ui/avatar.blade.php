@props(['user', 'previewId' => null])

{{-- The initials sit behind the photo, so they show whenever there is no image.
     The <img> is rendered only when there is a photo, or when a preview target
     is requested (avatar page, where JS fills it in before the upload). --}}
<span {{ $attributes->merge(['class' => 'relative inline-flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full bg-primary-soft font-semibold text-on-primary-soft']) }}>
    <span aria-hidden="true">{{ $user->initials }}</span>

    @if ($user->avatar_url || $previewId)
        <img
            @if ($previewId) id="{{ $previewId }}" @endif
            @if ($user->avatar_url) src="{{ $user->avatar_url }}" @endif
            alt="Foto de {{ $user->name }}"
            @class(['absolute inset-0 size-full object-cover', 'hidden' => ! $user->avatar_url])
        >
    @endif
</span>