<div class="max-w-5xl mx-auto">
    <x-mary-card title="FreeRADIUS Setup Guide for RadTik" separator class="bg-base-100">
        <x-slot:menu>
            <x-mary-button label="Back to RADIUS Servers" icon="o-arrow-left" link="/radius" wire:navigate class="btn-ghost btn-sm" />
        </x-slot:menu>

        <div class="prose prose-sm max-w-none dark:prose-invert">
            {{-- Introduction --}}
            <div class="bg-info/10 border-l-4 border-info rounded-r-lg p-4 mb-6">
                <div class="flex gap-3">
                    <x-mary-icon name="o-information-circle" class="w-6 h-6 text-info flex-shrink-0" />
                    <div>
                        <h3 class="font-bold text-base mb-1">Complete Setup Guide</h3>
                        <p class="text-sm">This guide walks you through installing and configuring <strong>FreeRADIUS with SQLite</strong> on <strong>Ubuntu 22.04</strong> for use with RadTik.</p>
                    </div>
                </div>
            </div>

            {{-- Requirements --}}
            <div class="bg-base-200 rounded-lg p-4 mb-6 border border-base-300">
                <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                    <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success" />
                    Requirements
                </h3>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                        <span class="text-sm">Ubuntu 22.04 server</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                        <span class="text-sm">Root or sudo access</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                        <span class="text-sm">Internet connection</span>
                    </div>
                </div>
            </div>

            {{-- Step 1 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">1</div>
                    <h2 class="text-xl font-bold">Install FreeRADIUS + Tools</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Update system and install FreeRADIUS with required packages:</p>
                <x-command-block 
                    code="sudo apt update
sudo apt install freeradius freeradius-utils sqlite3" 
                    language="bash" />
                
                <p class="mt-3 mb-3 text-sm leading-relaxed">Verify installation:</p>
                <x-command-block code="freeradius -v" language="bash" />
            </div>

            {{-- Step 2 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">2</div>
                    <h2 class="text-xl font-bold">Create SQLite Database</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Create the database directory:</p>
                <x-command-block code="sudo mkdir -p /etc/freeradius/3.0/sqlite" language="bash" />
                
                <p class="mt-3 mb-3 text-sm leading-relaxed">Create the database file:</p>
                <x-command-block code="sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db
.quit" language="bash" />
            </div>

            {{-- Step 3 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">3</div>
                    <h2 class="text-xl font-bold">Import FreeRADIUS SQLite Schema</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Import the default schema:</p>
                <x-command-block code="sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db < /etc/freeradius/3.0/mods-config/sql/main/sqlite/schema.sql" language="bash" />
            </div>

            {{-- Step 4 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">4</div>
                    <h2 class="text-xl font-bold">Fix Database Permissions</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Set correct ownership and permissions:</p>
                <x-command-block code="sudo chown freerad:freerad /etc/freeradius/3.0/sqlite/radius.db
sudo chmod 664 /etc/freeradius/3.0/sqlite/radius.db" language="bash" />
            </div>

            {{-- Step 5 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">5</div>
                    <h2 class="text-xl font-bold">Configure SQL Module</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Open the SQL configuration file:</p>
                <x-command-block code="sudo nano /etc/freeradius/3.0/mods-available/sql" language="bash" />
                
                <div class="bg-warning/10 border-l-4 border-warning rounded-r-lg p-4 my-4">
                    <div class="flex gap-3">
                        <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-warning flex-shrink-0 mt-1" />
                        <div>
                            <p class="font-bold text-base mb-2">Manual Configuration Required</p>
                            <p class="text-sm mb-3">Update these settings in the file:</p>
                            <div class="bg-base-200 rounded-lg p-3 space-y-2 font-mono text-xs">
                                <div><span class="text-primary">driver</span> = <span class="text-success">rlm_sql_sqlite</span></div>
                                <div><span class="text-primary">dialect</span> = <span class="text-success">sqlite</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="mt-3 mb-3 text-sm leading-relaxed">Find the sqlite block and ensure it looks like this:</p>
                <x-command-block code="sqlite {
    filename = /etc/freeradius/3.0/sqlite/radius.db
}" language="conf" />
            </div>

            {{-- Step 6 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">6</div>
                    <h2 class="text-xl font-bold">Enable SQL Module</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Create a symbolic link to enable the SQL module:</p>
                <x-command-block code="sudo ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/sql" language="bash" />
            </div>

            {{-- Step 7 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">7</div>
                    <h2 class="text-xl font-bold">Configure Client (RadTik)</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Edit the clients configuration:</p>
                <x-command-block code="sudo nano /etc/freeradius/3.0/clients.conf" language="bash" />
                
                <p class="mt-3 mb-3 text-sm leading-relaxed">Add this client configuration (replace secret in production):</p>
                <x-command-block code="client radtik {
    ipaddr = 0.0.0.0/0
    secret = testing123
    require_message_authenticator = no
}" language="conf" />
                
                <div class="bg-error/10 border-l-4 border-error rounded-r-lg p-4 my-4">
                    <div class="flex gap-3">
                        <x-mary-icon name="o-shield-exclamation" class="w-6 h-6 text-error flex-shrink-0" />
                        <div>
                            <p class="font-bold text-base text-error">Security Warning</p>
                            <p class="text-sm mt-1">Change the <code class="px-1.5 py-0.5 bg-error/20 rounded text-xs font-mono">secret</code> to a strong password in production!</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 8 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">8</div>
                    <h2 class="text-xl font-bold">Disable radpostauth Logging <span class="badge badge-warning badge-sm ml-2">Important</span></h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Edit the default site configuration:</p>
                <x-command-block code="sudo nano /etc/freeradius/3.0/sites-enabled/default" language="bash" />
                
                <div class="bg-warning/10 border-l-4 border-warning rounded-r-lg p-4 my-4">
                    <div class="flex gap-3">
                        <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-warning flex-shrink-0 mt-1" />
                        <div>
                            <p class="font-bold text-base mb-2 text-warning">Important Configuration Change</p>
                            <p class="text-sm mb-3">Remove or comment out <code class="px-1.5 py-0.5 bg-base-300 rounded text-xs">sql</code> from these sections:</p>
                            <div class="bg-base-200 rounded-lg p-3 space-y-1.5 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                    <code class="text-xs font-mono">post-auth { }</code>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                    <code class="text-xs font-mono">Post-Auth-Type REJECT { }</code>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                    <code class="text-xs font-mono">Post-Auth-Type Challenge { }</code>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 bg-error/10 rounded-lg p-3">
                                <x-mary-icon name="o-shield-exclamation" class="w-4 h-4 text-error flex-shrink-0 mt-0.5" />
                                <p class="text-sm font-semibold text-error">This prevents SQLite lock errors!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 9 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">9</div>
                    <h2 class="text-xl font-bold">Disable Accounting <span class="badge badge-sm ml-2">Optional</span></h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">In the same file, you can optionally disable accounting by removing <code class="px-1.5 py-0.5 bg-base-300 rounded text-xs font-mono">sql</code> from:</p>
                <x-command-block code="accounting { }" language="conf" />
            </div>

            {{-- Step 10 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">10</div>
                    <h2 class="text-xl font-bold">Restart FreeRADIUS</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Restart the service to apply all changes:</p>
                <x-command-block code="sudo systemctl restart freeradius" language="bash" />
                
                <p class="mt-3 mb-3 text-sm leading-relaxed">Check the service status:</p>
                <x-command-block code="sudo systemctl status freeradius" language="bash" />
            </div>

            {{-- Step 11 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">11</div>
                    <h2 class="text-xl font-bold">Add Test User</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Create a test user to verify the setup:</p>
                <x-command-block code="sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db \"INSERT INTO radcheck (username, attribute, op, value) VALUES ('testuser','Cleartext-Password',':=','testpass');\"" language="bash" />
            </div>

            {{-- Step 12 --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="badge badge-primary badge-lg px-4 py-3 text-base font-bold">12</div>
                    <h2 class="text-xl font-bold">Test Authentication</h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Test the RADIUS server:</p>
                <x-command-block code="radtest testuser testpass 127.0.0.1 0 testing123" language="bash" />
                
                <div class="bg-success/10 border-l-4 border-success rounded-r-lg p-4 my-4">
                    <div class="flex gap-3">
                        <x-mary-icon name="o-check-circle" class="w-6 h-6 text-success flex-shrink-0" />
                        <div class="flex-1">
                            <p class="font-bold text-base mb-3 text-success">Expected Result:</p>
                            <div class="bg-success/5 border border-success/20 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-mono text-success/70">RADIUS Response</span>
                                    <div class="badge badge-success badge-sm gap-1">
                                        <x-mary-icon name="o-check" class="w-3 h-3" />
                                        Success
                                    </div>
                                </div>
                                <code class="block text-lg font-bold font-mono text-success">Access-Accept</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Debug Mode --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <x-mary-icon name="o-bug-ant" class="w-7 h-7 text-warning p-1.5 bg-warning/10 rounded-lg" />
                    <h2 class="text-xl font-bold">Debug Mode <span class="badge badge-sm ml-2">Optional</span></h2>
                </div>
                <p class="mb-3 text-sm leading-relaxed">Run FreeRADIUS in debug mode to see live authentication logs:</p>
                <x-command-block code="sudo freeradius -X" language="bash" />
            </div>

            {{-- Important Notes --}}
            <div class="bg-primary/10 border-l-4 border-primary rounded-r-lg p-5 mb-8">
                <div class="flex gap-4">
                    <x-mary-icon name="o-light-bulb" class="w-7 h-7 text-primary flex-shrink-0 mt-1" />
                    <div class="flex-1">
                        <h3 class="text-lg font-bold mb-3 text-primary">Important Notes for RadTik</h3>
                        <div class="space-y-2.5">
                            <div class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 flex-shrink-0"></span>
                                <p class="text-sm">RadTik uses the <code class="px-1.5 py-0.5 bg-primary/20 rounded text-xs font-mono">radcheck</code> table for user authentication</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 flex-shrink-0"></span>
                                <p class="text-sm">SQLite is suitable for small to medium deployments</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 flex-shrink-0"></span>
                                <p class="text-sm">Always disable radpostauth logging to prevent database locks</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 flex-shrink-0"></span>
                                <p class="text-sm">Use strong shared secrets in production environments</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 flex-shrink-0"></span>
                                <p class="text-sm">Consider MySQL/PostgreSQL for large-scale deployments</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Success Message --}}
            <div class="bg-success/10 border-l-4 border-success rounded-r-lg p-5 mt-8">
                <div class="flex gap-4">
                    <x-mary-icon name="o-check-badge" class="w-8 h-8 text-success flex-shrink-0" />
                    <div>
                        <h3 class="font-bold text-lg mb-2 text-success flex items-center gap-2">
                            Setup Complete! 
                            <span class="text-2xl">✅</span>
                        </h3>
                        <p class="text-sm">FreeRADIUS is now configured and ready to authenticate users for RadTik.</p>
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('radius.create') }}" wire:navigate class="btn btn-success btn-sm gap-2">
                                <x-mary-icon name="o-plus" class="w-4 h-4" />
                                Add RADIUS Server
                            </a>
                            <a href="{{ route('radius.index') }}" wire:navigate class="btn btn-ghost btn-sm gap-2">
                                <x-mary-icon name="o-server" class="w-4 h-4" />
                                View Servers
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-mary-card>
</div>
