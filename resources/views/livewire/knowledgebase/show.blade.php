<section class="w-full">
    {{-- Back Button --}}
    <div class="mb-4">
        <x-mary-button icon="o-arrow-left" label="Back to Knowledge Base" class="btn-sm btn-ghost"
            href="{{ route('knowledgebase.index') }}" wire:navigate />
    </div>

    {{-- Article Content --}}
    <x-mary-card class="max-w-4xl mx-auto bg-base-200 rounded-2xl shadow-sm">
        <div class="px-6 py-6 space-y-4">
            {{-- Category Badge --}}
            <div>
                <span class="badge badge-primary">{{ ucfirst($article->category) }}</span>
            </div>

            {{-- Title --}}
            <h1 class="text-3xl font-bold">{{ $article->title }}</h1>

            {{-- Meta Info --}}
            <div class="flex items-center gap-4 text-sm opacity-60">
                <span class="flex items-center gap-1">
                    <x-mary-icon name="o-calendar" class="w-4 h-4" />
                    {{ $article->created_at->format('M d, Y') }}
                </span>
                <span class="flex items-center gap-1">
                    <x-mary-icon name="o-clock" class="w-4 h-4" />
                    {{ $article->updated_at->diffForHumans() }}
                </span>
            </div>

            {{-- Divider --}}
            <div class="divider"></div>

            {{-- Article Content --}}
            <div class="prose prose-lg max-w-none">
                {!! nl2br(e($article->content)) !!}
            </div>
        </div>
    </x-mary-card>

    {{-- Back Button (Bottom) --}}
    <div class="mt-6 text-center">
        <x-mary-button icon="o-arrow-left" label="Back to Knowledge Base" class="btn-sm btn-primary"
            href="{{ route('knowledgebase.index') }}" wire:navigate />
    </div>
</section>
