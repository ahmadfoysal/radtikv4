<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Mary\Traits\Toast;

class ThemeSettings extends Component
{
    use Toast;

    public $defaultTheme = 'dark';
    public $availableThemes = [
        'light' => 'Light',
        'dark' => 'Dark',
        'corporate' => 'Corporate',
        'business' => 'Business',
        'retro' => 'Retro',
        'cyberpunk' => 'Cyberpunk',
        'valentine' => 'Valentine',
        'halloween' => 'Halloween',
        'garden' => 'Garden',
        'forest' => 'Forest',
        'aqua' => 'Aqua',
        'lofi' => 'Lofi',
        'pastel' => 'Pastel',
        'fantasy' => 'Fantasy',
        'wireframe' => 'Wireframe',
        'black' => 'Black',
        'luxury' => 'Luxury',
        'dracula' => 'Dracula',
        'cmyk' => 'CMYK',
        'autumn' => 'Autumn',
        'nord' => 'Nord',
        'sunset' => 'Sunset',
    ];

    public function mount(): void
    {
        // Check if user is admin/superadmin
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()), 403);

        // Load current default theme from config
        $this->defaultTheme = config('theme.default_theme', 'dark');
    }

    public function render(): View
    {
        return view('livewire.admin.theme-settings');
    }

    public function save(): void
    {
        // Update config file
        $configPath = config_path('theme.php');
        $config = File::get($configPath);

        // Update default_theme value
        $config = preg_replace(
            "/'default_theme'\s*=>\s*env\('APP_DEFAULT_THEME',\s*'[^']*'\),/",
            "'default_theme' => env('APP_DEFAULT_THEME', '{$this->defaultTheme}'),",
            $config
        );

        File::put($configPath, $config);

        // Also update .env if it exists
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $env = File::get($envPath);
            if (str_contains($env, 'APP_DEFAULT_THEME=')) {
                $env = preg_replace('/APP_DEFAULT_THEME=.*/', "APP_DEFAULT_THEME={$this->defaultTheme}", $env);
            } else {
                $env .= "\nAPP_DEFAULT_THEME={$this->defaultTheme}\n";
            }
            File::put($envPath, $env);
        }

        // Clear config cache
        Artisan::call('config:clear');

        $this->success('Default theme updated successfully. The change will apply to new users.');
    }
}
