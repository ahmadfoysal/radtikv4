<div class="w-full max-w-6xl mx-auto px-4 py-6">
    {{-- Header Section --}}
    <div class="mb-6">
        <x-mary-button icon="o-arrow-left" label="Back to Knowledge Base" class="btn-sm btn-ghost mb-4"
            href="{{ route('knowledgebase.index') }}" wire:navigate />
        
        <div class="flex items-center gap-2 mb-3">
            <x-mary-badge value="{{ ucfirst($article->category) }}" class="badge-success badge-lg" />
            <span class="text-sm text-base-content/60">Knowledge Base</span>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        {{-- Main Content --}}
        <article class="lg:col-span-8">
            <x-mary-card class="bg-base-100 border border-base-300 shadow-sm">
                {{-- Article Header --}}
                <div class="border-b border-base-300 pb-6 mb-6">
                    <h1 class="text-4xl font-bold mb-4 leading-tight">{{ $article->title }}</h1>
                    
                    {{-- Meta Information --}}
                    <div class="flex flex-wrap items-center gap-4 text-sm text-base-content/70">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-calendar-days" class="w-4 h-4" />
                            <span>Published {{ $article->created_at->format('F d, Y') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-clock" class="w-4 h-4" />
                            <span>Updated {{ $article->updated_at->diffForHumans() }}</span>
                        </div>
                        @php
                            $wordCount = str_word_count(strip_tags($article->content));
                            $readingTime = max(1, ceil($wordCount / 200));
                        @endphp
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-book-open" class="w-4 h-4" />
                            <span>{{ $readingTime }} min read</span>
                        </div>
                    </div>
                </div>

                {{-- Article Content --}}
                <div class="prose prose-lg prose-slate dark:prose-invert max-w-none">
                    <div class="article-content text-base leading-relaxed">
                        {!! nl2br(e($article->content)) !!}
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="mt-8 pt-6 border-t border-base-300 flex items-center justify-between">
                    <x-mary-button icon="o-arrow-left" label="Back to Knowledge Base" class="btn-sm btn-ghost"
                        href="{{ route('knowledgebase.index') }}" wire:navigate />
                    
                    <div class="flex items-center gap-2">
                        <x-mary-button icon="o-share" label="Share" class="btn-sm btn-ghost" />
                    </div>
                </div>
            </x-mary-card>
        </article>

        {{-- Sidebar --}}
        <aside class="lg:col-span-4">
            <div class="space-y-4 sticky top-6">
                {{-- Quick Actions --}}
                <x-mary-card class="bg-base-100 border border-base-300">
                    <x-slot name="title">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-bolt" class="w-5 h-5" />
                            <span>Quick Actions</span>
                        </div>
                    </x-slot>
                    <div class="space-y-2">
                        <x-mary-button icon="o-arrow-left" label="All Articles" class="btn-sm btn-block btn-ghost justify-start"
                            href="{{ route('knowledgebase.index') }}" wire:navigate />
                        <x-mary-button icon="o-book-open" label="Documentation" class="btn-sm btn-block btn-ghost justify-start"
                            href="{{ route('docs.index') }}" wire:navigate />
                    </div>
                </x-mary-card>

                {{-- Article Info --}}
                <x-mary-card class="bg-base-100 border border-base-300">
                    <x-slot name="title">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-information-circle" class="w-5 h-5" />
                            <span>Article Info</span>
                        </div>
                    </x-slot>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Category</span>
                            <x-mary-badge value="{{ ucfirst($article->category) }}" class="badge-success badge-sm" />
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Published</span>
                            <span class="font-medium">{{ $article->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Last Updated</span>
                            <span class="font-medium">{{ $article->updated_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Reading Time</span>
                            <span class="font-medium">{{ $readingTime }} minutes</span>
                        </div>
                    </div>
                </x-mary-card>

                {{-- Help Section --}}
                <x-mary-card class="bg-base-100 border border-base-300">
                    <x-slot name="title">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-lifebuoy" class="w-5 h-5" />
                            <span>Need Help?</span>
                        </div>
                    </x-slot>
                    <p class="text-sm text-base-content/70 mb-3">
                        Can't find what you're looking for? Contact our support team for assistance.
                    </p>
                    <x-mary-button icon="o-chat-bubble-left-right" label="Contact Support" class="btn-sm btn-block btn-primary"
                        href="/support/contact" wire:navigate />
                </x-mary-card>
            </div>
        </aside>
    </div>

    <style>
.article-content {
    color: hsl(var(--bc));
    line-height: 1.8;
}

.article-content p {
    margin-bottom: 1.25rem;
}

.article-content h2 {
    font-size: 1.875rem;
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: hsl(var(--bc));
    border-bottom: 2px solid hsl(var(--b3));
    padding-bottom: 0.5rem;
}

.article-content h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: hsl(var(--bc));
}

.article-content ul,
.article-content ol {
    margin-left: 1.5rem;
    margin-bottom: 1.25rem;
}

.article-content li {
    margin-bottom: 0.5rem;
}

.article-content code {
    background-color: hsl(var(--b2));
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
    font-family: monospace;
}

.article-content pre {
    background-color: hsl(var(--b2));
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 1.25rem;
}

.article-content pre code {
    background-color: transparent;
    padding: 0;
}

.article-content blockquote {
    border-left: 4px solid hsl(var(--s));
    padding-left: 1rem;
    margin-left: 0;
    margin-bottom: 1.25rem;
    font-style: italic;
    color: hsl(var(--bc) / 0.8);
}

.article-content a {
    color: hsl(var(--s));
    text-decoration: underline;
}

.article-content a:hover {
    color: hsl(var(--sf));
}
    </style>
</div>
