<x-mary-card title="{{ __('Import Routers') }}" separator class="max-w-4xl mx-auto bg-base-100 shadow-md">

    {{-- Tabs --}}
    <x-mary-tabs wire:model="selectedTab" class="mb-6">
        {{-- === MIKHMON TAB === --}}
        <x-mary-tab name="mikhmon" label="Import from Mikhmon" icon="o-server">
            <x-mary-alert title="{{ __('Mikhmon Config File Location') }}"
                description="{{ __('Rename config.php to config.txt before uploading. Only .txt files are accepted for security.') }}"
                icon="o-shield-check" class="alert-warning mb-4" />

            <x-mary-form wire:submit="importMikhmon" class="mt-4">
                <div class="grid gap-4">

                    {{-- File input --}}
                    <div>
                        <x-mary-file label="{{ __('Select Mikhmon config.txt') }}" accept=".txt"
                            wire:model="configFile" hint="Only .txt files (max 2MB)" />

                        {{-- Error message --}}
                        @error('configFile')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror

                        {{-- upload progress --}}
                        <div class="mt-2" wire:loading wire:target="configFile">
                            <x-mary-loading class="w-4 h-4" />
                            <span class="text-sm opacity-70">{{ __('Uploading and parsing...') }}</span>
                        </div>
                    </div>

                    {{-- Preview table --}}
                    @if ($parsedReady && !empty($parsed))
                        <div class="mt-2" wire:key="config-preview-{{ md5(json_encode($parsed)) }}">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold">
                                    {{ __('Found') }}: {{ count($parsed) }} {{ __('router(s)') }}
                                </div>
                                <div class="text-sm opacity-70">
                                    {{ __('Only valid entries with host, port, username & password are shown.') }}
                                </div>
                            </div>

                            <div class="overflow-x-auto border border-base-300 bg-base-100 rounded-lg">
                                <table class="table table-zebra text-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Address') }}</th>
                                            <th>{{ __('Port') }}</th>
                                            <th>{{ __('Username') }}</th>
                                            <th>{{ __('Login Address') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($parsed as $index => $r)
                                            <tr wire:key="router-{{ $index }}">
                                                <td class="whitespace-nowrap">{{ $r['name'] }}</td>
                                                <td class="whitespace-nowrap">{{ $r['address'] }}</td>
                                                <td>{{ $r['port'] }}</td>
                                                <td class="whitespace-nowrap">{{ $r['username'] }}</td>
                                                <td class="max-w-[280px]">
                                                    <span class="truncate inline-block align-middle"
                                                        title="{{ $r['login_address'] ?? '' }}">
                                                        {{ $r['login_address'] ?? '—' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Skip Existing Checkbox --}}
                            <div class="mt-4">
                                <label class="cursor-pointer label justify-start gap-2">
                                    <input type="checkbox" wire:model="skipExisting"
                                        class="checkbox checkbox-primary" />
                                    <span
                                        class="label-text">{{ __('Skip existing routers (same address and port)') }}</span>
                                </label>
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

        {{-- === CSV TAB === --}}
        <x-mary-tab name="csv" label="Import from CSV" icon="o-table-cells">
            <x-mary-alert title="{{ __('CSV File Format') }}"
                description="{{ __('CSV file should contain columns: name, address, port, username, password, login_address (optional), note (optional). Maximum 1000 rows, 2MB file size.') }}"
                icon="o-information-circle" class="alert-info mb-4" />

            {{-- Security Notice --}}
            <div class="mb-4 p-3 bg-warning bg-opacity-10 border border-warning rounded-lg">
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning flex-shrink-0 mt-0.5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-sm">
                        <strong>{{ __('Security Notice:') }}</strong>
                        {{ __('All uploaded files are validated and sanitized. Do not upload executable files or files from untrusted sources.') }}
                    </div>
                </div>
            </div>

            {{-- Sample CSV Format --}}
            <div class="mb-4 p-4 bg-base-200 rounded-lg">
                <div class="font-semibold mb-2">{{ __('Sample CSV Format:') }}</div>
                <pre class="text-xs overflow-x-auto">name,address,port,username,password,login_address,note
Router 1,192.168.1.1,8728,admin,password123,login.example.com,Main Router
Router 2,192.168.1.2,8728,admin,password456,,Backup Router</pre>
            </div>

            <x-mary-form wire:submit="importCsv" class="mt-4">
                <div class="grid gap-4">
                    <div>
                        <x-mary-file label="{{ __('Select CSV file') }}" accept=".csv,.txt" wire:model="csvFile"
                            hint="Only .csv or .txt files (max 2MB, 1000 rows)" />

                        <div wire:loading wire:target="csvFile" class="mt-2">
                            <x-mary-loading class="w-4 h-4" />
                            <span class="text-sm opacity-70">{{ __('Uploading and parsing...') }}</span>
                        </div>

                        @error('csvFile')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Preview table --}}
                    @if ($csvParsedReady)
                        <div wire:loading.remove wire:target="csvFile" class="mt-2">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold">
                                    {{ __('Found') }}: {{ count($csvParsed) }} {{ __('router(s)') }}
                                </div>
                                <div class="text-sm opacity-70">
                                    {{ __('Only valid entries with required fields are shown.') }}
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
                                            <th>{{ __('Login Address') }}</th>
                                            <th>{{ __('Note') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($csvParsed as $r)
                                            <tr>
                                                <td class="whitespace-nowrap">{{ $r['name'] }}</td>
                                                <td class="whitespace-nowrap">{{ $r['address'] }}</td>
                                                <td>{{ $r['port'] }}</td>
                                                <td class="whitespace-nowrap">{{ $r['username'] }}</td>
                                                <td class="max-w-[200px]">
                                                    <span class="truncate inline-block align-middle"
                                                        title="{{ $r['login_address'] ?? '' }}">
                                                        {{ $r['login_address'] ?? '—' }}
                                                    </span>
                                                </td>
                                                <td class="max-w-[200px]">
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

                            {{-- Skip Existing Checkbox --}}
                            <div class="mt-4">
                                <label class="cursor-pointer label justify-start gap-2">
                                    <input type="checkbox" wire:model="skipExisting"
                                        class="checkbox checkbox-primary" />
                                    <span
                                        class="label-text">{{ __('Skip existing routers (same address and port)') }}</span>
                                </label>
                            </div>
                        </div>
                    @endif
                </div>

                <x-slot:actions>
                    <x-mary-button type="button" class="btn-ghost" wire:click="cancel">
                        {{ __('Cancel') }}
                    </x-mary-button>

                    <x-mary-button type="submit" class="btn-primary" label="{{ __('Import from CSV') }}"
                        spinner="importCsv" :disabled="!$csvParsedReady || empty($csvParsed)" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-tab>
    </x-mary-tabs>
</x-mary-card>
