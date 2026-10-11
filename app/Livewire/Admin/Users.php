<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('admin');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function changeRole(int $userId, string $role): void
    {
        $this->authorize('admin');

        $user = $this->findOtherUser($userId);
        $user->role = Role::from($role);
        $user->save();

        Flux::toast(variant: 'success', text: __('admin.users.role_updated', ['name' => $user->name]));
    }

    public function deleteUser(int $userId): void
    {
        $this->authorize('admin');

        $user = $this->findOtherUser($userId);
        $user->delete();

        Flux::toast(variant: 'success', text: __('admin.users.deleted', ['name' => $user->name]));
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return User::query()
            ->with('schoolClasses')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.admin.users')
            ->title(__('admin.users.title'));
    }

    /**
     * Administrators cannot change or delete their own account from here.
     */
    private function findOtherUser(int $userId): User
    {
        abort_if($userId === Auth::id(), 403);

        return User::findOrFail($userId);
    }
}
