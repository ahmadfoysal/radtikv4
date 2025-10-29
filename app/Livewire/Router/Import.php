<?php

namespace App\Livewire\Router;

use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Import extends Component
{
    use WithFileUploads;

    public string $selectedTab = 'mikhmon';
    protected $queryString = [
        'selectedTab' => ['except' => 'mikhmon'],
    ];


    #[Rule(['required', 'file', 'max:2048', 'extensions:php'])]
    public $configFile;



    public bool $parsedReady = false;
    public bool $skipExisting = true;   // ডুপ্লিকেট এড়াতে address+port ভিত্তিক স্কিপ
    public array $parsed = [];          // প্রিভিউ টেবল দেখানোর জন্য

    public function updatedConfigFile(): void
    {
        $this->reset('parsed', 'parsedReady');
        $this->validateOnly('configFile');

        $contents = file_get_contents($this->configFile->getRealPath());


        $this->parsed = $this->parseMikhmonConfig($contents);

        if (empty($this->parsed)) {
            $this->addError('configFile', 'কোনো বৈধ রাউটার পাওয়া যায়নি। ফাইল ফরম্যাট চেক করুন।');
            return;
        }
        $this->parsedReady = true;
    }

    /** ইম্পোর্ট বাটন */
    public function import(): void
    {
        $this->validate();

        if (empty($this->parsed)) {
            $this->addError('configFile', 'প্রথমে একটি বৈধ কনফিগ ফাইল সিলেক্ট করুন।');
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($this->parsed as $item) {
            // address + port কে ইউনিক ধরা হলো
            $exists = Router::query()
                ->where('address', $item['address'])
                ->where('port', $item['port'])
                ->exists();

            if ($exists && $this->skipExisting) {
                $skipped++;
                continue;
            }

            // যদি exists কিন্তু skipExisting=false হয়, তাহলে আপডেট/আপসার্ট করতে পারেন—
            // আপাতত আমরা create-or-update আচরণ দিচ্ছি:
            $router = Router::updateOrCreate(
                ['address' => $item['address'], 'port' => $item['port']],
                [
                    'name'     => $item['name'],
                    'username' => $item['username'],
                    'password' => Crypt::encryptString($item['password']),
                    'note'     => $item['note'] ?? null,
                    'user_id'  => Auth::id(),
                ]
            );

            $created++;
        }

        $this->dispatch(
            'notify',
            type: 'success',
            message: "Import successful: created/updated {$created}, skipped {$skipped}."
        );

        // ফর্ম রিসেট
        $this->reset(['configFile', 'parsed', 'parsedReady']);
        $this->skipExisting = true;
    }

    /** মিখমন কনফিগ পার্সার
     *  প্রত্যেক $data['KEY'] = array('...','...') ব্লক থেকে:
     *   - '1' => '<name>!<host>:<port>'
     *   - '<name>@|@<username>'
     *   - '<name>#|#<password>'  (base64 হতে পারে)
     *   - '<name>%<ssid>'        (ঐচ্ছিক, note-এ রাখছি)
     *   - '<name>^<domain>'      (ঐচ্ছিক, note-এ যোগ)
     *   - অন্যান্য ফ্ল্যাগ/স্টেটাসগুলো এখন দরকার নেই
     */
    protected function parseMikhmonConfig(string $contents): array
    {
        $routers = [];

        // প্রতিটি $data['key'] = array ( ... ); ব্লক ধরার জন্য regex
        if (!preg_match_all('/\$data\s*\[\'([^\']+)\'\]\s*=\s*array\s*\((.*?)\);/s', $contents, $matches, PREG_SET_ORDER)) {
            return $routers;
        }


        foreach ($matches as $block) {
            $key   = $block[1];      // e.g. 'Alhasa1'
            $body  = $block[2];

            // 'mikhmon' ব্লক স্কিপ
            if (Str::lower($key) === 'mikhmon') {
                continue;
            }

            // ব্লকের ভিতরের '...' স্ট্রিংগুলো বের করি
            if (!preg_match_all("/'([^']*)'/", $body, $lines)) {
                continue;
            }
            $values = $lines[1];

            $name = $key;
            $host = null;
            $port = null;
            $username = null;
            $password = null;
            $noteParts = [];

            foreach ($values as $v) {
                // 1) '1' => '<name>!host:port'
                if (str_contains($v, '!') && str_contains($v, ':') && ($host === null)) {
                    // left= name, right = host:port
                    [$left, $right] = explode('!', $v, 2);
                    $maybeName = trim($left);
                    if ($maybeName !== '') $name = $maybeName;

                    // host:port
                    if (str_contains($right, ':')) {
                        [$h, $p] = explode(':', $right, 2);
                        $host = trim($h);
                        $port = (int) trim($p);
                    }
                    continue;
                }

                // 2) '<name>@|@username'
                if (str_contains($v, '@|@') && $username === null) {
                    [, $username] = explode('@|@', $v, 2);
                    $username = trim($username);
                    continue;
                }

                // 3) '<name>#|#password'  (প্রায়ই base64)
                if (str_contains($v, '#|#') && $password === null) {
                    [, $pwd] = explode('#|#', $v, 2);
                    $pwd = trim($pwd);

                    // base64 ডিকোড ট্রাই (fail হলে original রাখি)
                    $decoded = base64_decode($pwd, true);
                    $password = ($decoded !== false) ? $decoded : $pwd;
                    continue;
                }

                // 4) '<name>%SSID'  → note
                if (str_contains($v, '%')) {
                    [, $ssid] = explode('%', $v, 2);
                    $ssid = trim($ssid);
                    if ($ssid !== '') $noteParts[] = "SSID: {$ssid}";
                    continue;
                }

                // 5) '<name>^domain' → note
                if (str_contains($v, '^')) {
                    [, $domain] = explode('^', $v, 2);
                    $domain = trim($domain);
                    if ($domain !== '') $noteParts[] = "Domain: {$domain}";
                    continue;
                }

                // অন্যান্য টোকেন (যেমন '&','*','@!@enable/disable') এখন এড়িয়ে যাচ্ছি
            }

            if ($host && $port && $username && $password) {
                $routers[] = [
                    'name'     => $name,
                    'address'  => $host,
                    'port'     => (int) $port,
                    'username' => $username,
                    'password' => $password,
                    'note'     => implode(' | ', $noteParts),
                ];
            }
        }

        return $routers;
    }

    public function importMikhmon()
    {
        $this->import();
    } // আগের import মেথডটা রিইউজ করছে

    public function importExcel()
    {
        $this->validate([
            'excelFile' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        // Excel import logic আপনি পরে যোগ করতে পারবেন (maatwebsite/excel বা PhpSpreadsheet দিয়ে)
        $this->dispatch('notify', type: 'info', message: 'Excel import coming soon!');
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
