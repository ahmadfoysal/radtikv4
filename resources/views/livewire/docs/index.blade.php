<section class="w-full">
    {{-- Header --}}
    <x-mary-card class="mb-6 bg-base-200 border-0 shadow-sm rounded-2xl">
        <div class="px-4 py-4 flex flex-col gap-3">
            <div class="flex items-center gap-2 mb-2">
                <x-mary-icon name="o-book-open" class="w-6 h-6 text-primary" />
                <span class="font-semibold text-lg">Documentation</span>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                {{-- Search Box --}}
                <x-mary-input placeholder="Search documentation..." icon="o-magnifying-glass" class="w-full sm:flex-1"
                    input-class="input-sm" wire:model.live.debounce.400ms="q" />

                {{-- Category Filter --}}
                <x-mary-select wire:model.live="category" class="w-full sm:w-64" :options="array_merge(
                    [['id' => 'all', 'name' => 'All Categories']],
                    array_map(fn($cat) => ['id' => $cat, 'name' => ucfirst($cat)], $categories)
                )" option-label="name" option-value="id" />
            </div>
        </div>
    </x-mary-card>

    {{-- Articles Grid --}}
    <div class="px-2 sm:px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($articles as $article)
                <x-mary-card class="bg-base-200 rounded-2xl shadow-sm hover:shadow-md transition duration-300">
                    <div class="space-y-3">
                        {{-- Category Badge --}}
                        <div>
                            <span class="badge badge-sm badge-primary">{{ ucfirst($article->category) }}</span>
                        </div>

                        {{-- Title --}}
                        <h3 class="font-semibold text-base line-clamp-2">
                            {{ $article->title }}
                        </h3>

                        {{-- Content Preview --}}
                        <p class="text-sm opacity-70 line-clamp-3">
                            {{ Str::limit(strip_tags($article->content), 150) }}
                        </p>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between pt-2">
                            <span class="text-xs opacity-60">
                                {{ $article->created_at->diffForHumans() }}
                            </span>
                            <x-mary-button icon="o-arrow-right" label="Read More" class="btn-xs btn-ghost"
                                href="{{ route('docs.show', $article->slug) }}" wire:navigate />
                        </div>
                    </div>
                </x-mary-card>
            @empty
                <x-mary-card class="col-span-full bg-base-200 rounded-2xl">
                    <div class="p-8 text-center opacity-70">
                        <x-mary-icon name="o-document-magnifying-glass" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <p>No documentation found.</p>
                    </div>
                </x-mary-card>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $articles->links() }}
        </div>
    </div>
</section>
