<div class="flex flex-col gap-1">
    <div class="text-xs text-base-content/60 font-medium">{{ $label ?? '' }}</div>
    <div class="text-base font-semibold text-base-content">
        @if (is_array($value))
            {{ implode(', ', $value) }}
        @else
            {{ $value ?? '' }}
        @endif
    </div>
</div>
