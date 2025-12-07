<?php

namespace App\Livewire\Router;

use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class Import extends Component
{
    use Toast, WithFileUploads;

    public string $selectedTab = 'mikhmon';

    protected $queryString = ['selectedTab' => ['except' => 'mikhmon']];

    // কেবল .php (চাইলে txt যোগ করুন)
    #[Rule(['required', 'file', 'max:4096', 'extensions:php'])]
    public $configFile;

    public bool $parsedReady = false;

    public bool $skipExisting = true;

    public array $parsed = [];

    public function updatedConfigFile(): void
    {
        $this->reset('parsed', 'parsedReady');
        $this->validateOnly('configFile');

        $contents = file_get_contents($this->configFile->getRealPath());
        $this->parsed = $this->parseMikhmonConfig($contents);

        if (empty($this->parsed)) {
            $this->addError('configFile', 'No valid router configurations found in the file.');

            return;
        }

        $this->parsedReady = true;
    }

    public function import(): void
    {
        $this->validate();

        if (empty($this->parsed)) {
            $this->addError('configFile', 'Please select a valid config file first.');

            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($this->parsed as $item) {
            $exists = Router::query()
                ->where('address', $item['address'])
                ->where('port', $item['port'])
                ->exists();

            if ($exists && $this->skipExisting) {
                $skipped++;

                continue;
            }

            // UI তে base64 ছিল → এখানে ডিকোড
            // $pwd = $this->decryptPassword($item['password_b64']);

            Router::updateOrCreate(
                ['address' => $item['address'], 'port' => (int) $item['port']],
                [
                    'name' => $item['name'],
                    'username' => $item['username'],
                    'password' => Crypt::encryptString($item['password']),
                    'note' => $item['note'] ?? null,
                    'app_key' => bin2hex(random_bytes(16)),
                    'user_id' => Auth::id(),
                ]
            );

            $created++;
        }

        $this->dispatch(
            'notify',
            type: 'success',
            message: "Import successful: created/updated {$created}, skipped {$skipped}."
        );

        $this->reset(['configFile', 'parsed', 'parsedReady']);
        $this->skipExisting = true;
    }

    protected function parseMikhmonConfig(string $contents): array
    {
        $routers = [];

        if (! preg_match_all(
            '/\$data\s*\[\'([^\']+)\'\]\s*=\s*array\s*\((.*?)\);/s',
            $contents,
            $matches,
            PREG_SET_ORDER
        )) {
            return $routers;
        }

        foreach ($matches as $block) {
            $key = $block[1];
            $body = $block[2];

            if (Str::lower($key) === 'mikhmon') {
                continue;
            }

            if (! preg_match_all("/'([^']*)'/", $body, $lines)) {
                continue;
            }
            $values = $lines[1];

            $name = $key;
            $host = null;
            $port = null;
            $username = null;
            $passwordRaw = null;
            $noteParts = [];

            foreach ($values as $v) {
                if (str_contains($v, '!') && str_contains($v, ':') && $host === null) {
                    [$left, $right] = explode('!', $v, 2);
                    $maybeName = trim($left);
                    if ($maybeName !== '') {
                        $name = $maybeName;
                    }
                    if (str_contains($right, ':')) {
                        [$h, $p] = explode(':', $right, 2);
                        $host = trim($h);
                        $port = (int) trim($p);
                    }

                    continue;
                }

                if (str_contains($v, '@|@') && $username === null) {
                    [, $username] = explode('@|@', $v, 2);
                    $username = trim($username);

                    continue;
                }

                if (str_contains($v, '#|#') && $passwordRaw === null) {
                    [, $pwd] = explode('#|#', $v, 2);
                    $passwordRaw = trim($pwd); // UI স্টেটে এখনই ডিকোড নয়

                    continue;
                }

                if (str_contains($v, '%')) {
                    [, $ssid] = explode('%', $v, 2);
                    $ssid = trim($ssid);
                    if ($ssid !== '') {
                        $noteParts[] = "SSID: {$ssid}";
                    }

                    continue;
                }

                if (str_contains($v, '^')) {
                    [, $domain] = explode('^', $v, 2);
                    $domain = trim($domain);
                    if ($domain !== '') {
                        $noteParts[] = "Domain: {$domain}";
                    }

                    continue;
                }
            }

            if ($host && $port && $username && $passwordRaw) {
                $routers[] = [
                    'name' => $name,
                    'address' => $host,
                    'port' => (int) $port,
                    'username' => $username,
                    // ✅ JSON-safe: UI স্টেটে base64
                    'password' => $this->decryptPassword($passwordRaw),
                    'note' => implode(' | ', $noteParts),
                ];
            }
        }

        return $routers;
    }

    public function decryptPassword($string, $key = 128): string
    {
        $result = '';
        $string = base64_decode($string);
        for ($i = 0, $k = strlen($string); $i < $k; $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }

        return $result;
    }

    public function importMikhmon()
    {
        $this->import();

        $this->redirect(route('routers.index'), navigate: true);

        $this->success('Routers imported successfully.');
    }

    public function cancel()
    {
        $this->redirect(route('routers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.router.import');
    }
}
