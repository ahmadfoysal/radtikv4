@props(['code', 'language' => 'bash'])

<div class="relative group mb-4">
    <div class="bg-base-300 rounded-lg border border-base-content/10 shadow-sm">
        <div class="flex items-center justify-between px-4 py-2 border-b border-base-content/10 bg-base-200/50">
            <span class="text-xs font-mono text-base-content/60">{{ $language }}</span>
            <button 
                onclick="copyToClipboard(this, {{ json_encode($code) }})" 
                class="btn btn-xs btn-ghost gap-1 opacity-70 hover:opacity-100 transition-opacity"
                title="Copy to clipboard">
                <x-mary-icon name="o-clipboard" class="w-3.5 h-3.5 copy-icon" />
                <span class="copy-text text-xs">Copy</span>
                <x-mary-icon name="o-check" class="w-3.5 h-3.5 check-icon hidden text-success" />
                <span class="check-text text-xs hidden">Copied!</span>
            </button>
        </div>
        <div class="p-4 overflow-x-auto">
            <pre class="text-sm"><code class="language-{{ $language }} text-base-content">{{ $code }}</code></pre>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function copyToClipboard(button, text) {
    const copyIcon = button.querySelector('.copy-icon');
    const checkIcon = button.querySelector('.check-icon');
    const copyText = button.querySelector('.copy-text');
    const checkText = button.querySelector('.check-text');
    
    navigator.clipboard.writeText(text).then(() => {
        // Show success
        copyIcon.classList.add('hidden');
        checkIcon.classList.remove('hidden');
        copyText.classList.add('hidden');
        checkText.classList.remove('hidden');
        
        // Reset after 2 seconds
        setTimeout(() => {
            copyIcon.classList.remove('hidden');
            checkIcon.classList.add('hidden');
            copyText.classList.remove('hidden');
            checkText.classList.add('hidden');
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy to clipboard');
    });
}
</script>
@endpush
@endonce
