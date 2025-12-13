<x-mary-card title="{{ __('Import Routers') }}" separator class="max-w-4xl mx-auto bg-base-100 shadow-md">

    {{-- Tabs --}}
    <x-mary-tabs wire:model="selectedTab" class="mb-6">
        {{-- === MIKHMON TAB === --}}
        <x-mary-tab name="mikhmon" label="Import from Mikhmon" icon="o-server">
            <x-mary-alert title="{{ __('Mikhmon Config File Location') }}"
                description="{{ __('Please select the file located at: mikhmon/include/config.php') }}"
                icon="o-folder-open" class="alert-success mb-4" />

            <x-mary-form wire:submit="importMikhmon" class="mt-4">
                <div class="grid gap-4">

                    {{-- File input --}}
                    <div>
                        <x-mary-file label="{{ __('Select Mikhmon config.php') }}" accept=".php, .txt"
                            wire:model="configFile" />

                        {{-- upload progress --}}
                        <div class="mt-2" wire:loading wire:target="configFile">
                            <x-mary-loading class="w-4 h-4" />
                            <span class="text-sm opacity-70">{{ __('Uploading...') }}</span>
                        </div>
                    </div>

                    {{-- Preview table --}}
                    @if ($parsedReady)
                        <div wire:loading.remove wire:target="configFile" class="mt-2">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold">
                                    {{ __('Found') }}: {{ count($parsed) }} {{ __('router(s)') }}
                                </div>
                                <div class="text-sm opacity-70">
                                    {{ __('Only valid entries with host, port, username & password are shown.') }}
                                </div>
                            </div>

                            <div class="overflow-x-auto border border-base-300 bg-base-100">
                                <table class="table table-zebra text-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Address') }}</th>
                                            <th>{{ __('Port') }}</th>
                                            <th>{{ __('Username') }}</th>
                                            <th>{{ __('Note') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($parsed as $r)
                                            <tr>
                                                <td class="whitespace-nowrap">{{ $r['name'] }}</td>
                                                <td class="whitespace-nowrap">{{ $r['address'] }}</td>
                                                <td>{{ $r['port'] }}</td>
                                                <td class="whitespace-nowrap">{{ $r['username'] }}</td>
                                                <td class="max-w-[280px]">
                                                    <span class="truncate inline-block align-middle"
                                                        title="{{ $r['note'] ?? '' }}">
                                                        {{ $r['note'] ?? '—' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <x-slot:actions>
                    <x-mary-button type="button" class="btn-ghost" wire:click="cancel">
                        {{ __('Cancel') }}
                    </x-mary-button>

                    <x-mary-button type="submit" class="btn-primary" label="{{ __('Import') }}"
                        spinner="importMikhmon" :disabled="!$parsedReady || empty($parsed)" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-tab>

        {{-- === EXCEL TAB === --}}
        <x-mary-tab name="excel" label="Import from Excel" icon="o-table-cells">
            <x-mary-alert title="{{ __('Import Excel File') }}"
                description="{{ __('Excel file should contain columns: name, address, port, username, password, note') }}"
                icon="o-folder-open" class="alert-info mb-4" />

            <x-mary-form wire:submit="importExcel" class="mt-4">
                <div class="grid gap-4">
                    <div>
                        <x-mary-file label="{{ __('Select Excel file (.xlsx)') }}" accept=".xlsx,.xls"
                            wire:model="excelFile" />

                        <div wire:loading wire:target="excelFile" class="mt-2">
                            <x-mary-loading class="w-4 h-4" />
                            <span class="text-sm opacity-70">{{ __('Uploading...') }}</span>
                        </div>

                        @error('excelFile')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <x-slot:actions>
                    <x-mary-button type="button" class="btn-ghost" wire:click="cancel">
                        {{ __('Cancel') }}
                    </x-mary-button>

                    <x-mary-button type="submit" class="btn-primary" label="{{ __('Import Excel') }}"
                        spinner="importExcel" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-tab>
    </x-mary-tabs>
</x-mary-card>
