<div class="max-w-7xl mx-auto">
    <x-mary-card title="Theme Settings" separator class=" bg-base-100">
        <p class="text-sm text-base-content/70 mb-6">
            Configure the default theme for the application. Users can still override this with their personal theme preference.
        </p>

        <x-mary-form wire:submit="save" class="space-y-6">
            <div>
                <label class="label">
                    <span class="label-text font-semibold">Default Theme</span>
                    <span class="label-text-alt text-base-content/60">Applied to new users and as fallback</span>
                </label>
                <select wire:model.live="defaultTheme" class="select select-bordered w-full max-w-xs focus:outline-primary">
                    @foreach($availableThemes as $themeKey => $themeName)
                        <option value="{{ $themeKey }}">{{ $themeName }}</option>
                    @endforeach
                </select>
                @error('defaultTheme')
                    <div class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="divider"></div>

            <div>
                <label class="label">
                    <span class="label-text font-semibold">Theme Preview</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 mt-2">
                    @foreach($availableThemes as $themeKey => $themeName)
                        <div 
                            class="card bg-base-100 border-2 cursor-pointer transition-all hover:scale-105
                                {{ $defaultTheme === $themeKey ? 'border-primary shadow-lg' : 'border-base-300' }}"
                            wire:click="$set('defaultTheme', '{{ $themeKey }}')"
                        >
                            <div class="card-body p-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-primary"></div>
                                    <div class="flex-1 text-xs font-medium text-base-content truncate">{{ $themeName }}</div>
                                </div>
                                @if($defaultTheme === $themeKey)
                                    <div class="badge badge-primary badge-sm mt-1">Default</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Save Theme Settings" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>
</div>
