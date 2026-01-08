<?php

namespace App\Livewire\Admin;

use App\Models\ContactMessage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('components.layouts.app')]
#[Title('Contact Messages')]
class ContactMessages extends Component
{
    use AuthorizesRequests, Toast, WithPagination;

    public string $search = '';

    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    public function mount(): void
    {
        // Only superadmin can access
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            ContactMessage::find($this->deleteId)?->delete();
            $this->success('Contact message deleted successfully');
        }

        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    public function render()
    {
        $messages = ContactMessage::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('subject', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.contact-messages', [
            'messages' => $messages,
        ]);
    }
}
