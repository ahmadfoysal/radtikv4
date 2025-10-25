<?php

namespace App\Livewire\Router;

use Livewire\Component;

class Create extends Component
{
    public array $form = [
        'name'           => '',
        'host'           => '',
        'protocol'       => 'api', // api | ssh
        'port'           => null,
        'username'       => '',
        'password'       => '',
        'group_id'       => null,
        'profile_id'     => null,
        'ros_version'    => '',
        'snmp_community' => '',
        'shared_secret'  => '',
        'enabled'        => true,
        'description'    => '',
        'location'       => '',
    ];

    public array $groups   = []; // [['id'=>1,'name'=>'Default'], ...]
    public array $profiles = []; // [['id'=>1,'name'=>'Basic'], ...]

    public function mount()
    {
        // উদাহরণ ডাটা; বাস্তবে DB থেকে আনবেন
        $this->groups   = [['id' => 1, 'name' => 'Default'], ['id' => 2, 'name' => 'Branch A']];
        $this->profiles = [['id' => 1, 'name' => 'Basic'], ['id' => 2, 'name' => 'Business']];

        // default port
        $this->form['port'] = $this->form['protocol'] === 'ssh' ? 22 : 8728;
    }

    protected function rules()
    {
        return [
            'form.name'       => ['required', 'string', 'max:100'],
            'form.host'       => ['required', 'string', 'max:190'],
            'form.protocol'   => ['required', Rule::in(['api', 'ssh'])],
            'form.port'       => ['required', 'integer', 'between:1,65535'],
            'form.username'   => ['required', 'string', 'max:100'],
            'form.password'   => ['required', 'string', 'max:190'],
            'form.group_id'   => ['nullable', 'integer'],
            'form.profile_id' => ['nullable', 'integer'],
            'form.ros_version' => ['nullable', 'string', 'max:20'],
            'form.snmp_community' => ['nullable', 'string', 'max:50'],
            'form.shared_secret'  => ['nullable', 'string', 'max:190'],
            'form.enabled'    => ['boolean'],
            'form.description' => ['nullable', 'string', 'max:500'],
            'form.location'   => ['nullable', 'string', 'max:190'],
        ];
    }

    public function updatedFormProtocol($value)
    {
        // auto-switch port
        $this->form['port'] = $value === 'ssh' ? 22 : 8728;
    }

    public function testConnection()
    {
        $this->validate([
            'form.host'     => ['required', 'string'],
            'form.username' => ['required'],
            'form.password' => ['required'],
            'form.port'     => ['required', 'integer'],
            'form.protocol' => ['required'],
        ]);

        // এখানে MikroTik API/SSH দিয়ে পিং/লগইন টেস্ট করবেন
        // উদাহরণস্বরূপ সফল ধরে নিচ্ছি:
        $ok = true;

        if ($ok) {
            $this->dispatch('toast', type: 'success', title: 'Connection OK', description: 'Router reachable.');
        } else {
            $this->dispatch('toast', type: 'error', title: 'Connection Failed', description: 'Check host/port/credentials.');
        }
    }

    public function save()
    {
        $data = $this->validate()['form'];

        // ✅ TODO: credentials নিরাপদে সংরক্ষণ করুন (hashed/encrypted column)
        // Router::create([... $data ...]);

        $this->dispatch('toast', type: 'success', title: 'Router Saved', description: $data['name'] . ' created.');
        // return redirect()->route('routers.index'); // চাইলে রিডাইরেক্ট
    }

    public function cancel()
    {
        // রিডাইরেক্ট বা ফর্ম রিসেট করুন
        $this->dispatch('toast', type: 'info', title: 'Cancelled', description: 'Router creation cancelled.');
        return redirect()->route('routers.index');
    }

    public function render()
    {
        return view('livewire.router.create')
            ->layout('components.layouts.app', ['title' => 'Add Router – RadTik']);
    }
}
