<?php

namespace App\Livewire\Admin;

use App\Auth\Impersonation;
use App\Auth\Invitations;
use App\Enums\Role;
use App\Models\Stand;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public string $search = '';

    public string $inviteFirstName = '';

    public string $inviteLastName = '';

    public string $inviteEmail = '';

    /** @var array<int, int|string> */
    public array $inviteStandIds = [];

    public string $inviteMessage = '';

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

    /**
     * Create the account of someone else and email them the link to activate it.
     */
    public function invite(Invitations $invitations): void
    {
        $this->authorize('admin');

        $this->inviteEmail = Str::lower(trim($this->inviteEmail));

        $validated = $this->validate([
            'inviteFirstName' => ['required', 'string', 'max:100'],
            'inviteLastName' => ['required', 'string', 'max:100'],
            'inviteEmail' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'inviteStandIds' => ['array'],
            'inviteStandIds.*' => ['integer', Rule::exists(Stand::class, 'id')],
            'inviteMessage' => ['nullable', 'string', 'max:1000'],
        ], [
            'inviteEmail.unique' => __('invitations.admin.email_taken'),
        ], [
            'inviteFirstName' => __('fields.first_name'),
            'inviteLastName' => __('fields.last_name'),
            'inviteEmail' => __('fields.email'),
        ]);

        $user = $invitations->invite(
            trim($validated['inviteFirstName']),
            trim($validated['inviteLastName']),
            $validated['inviteEmail'],
            array_map(intval(...), $validated['inviteStandIds'] ?? []),
            $validated['inviteMessage'] ?: null,
        );

        $this->reset('inviteFirstName', 'inviteLastName', 'inviteEmail', 'inviteStandIds', 'inviteMessage');
        unset($this->users);

        Flux::modal('invite')->close();
        Flux::toast(variant: 'success', text: __('invitations.admin.sent', ['email' => $user->email]));
    }

    public function resendInvitation(int $userId, Invitations $invitations): void
    {
        $this->authorize('admin');

        $user = User::findOrFail($userId);

        abort_unless($invitations->isPending($user), 403);

        $invitations->send($user);

        Flux::toast(variant: 'success', text: __('invitations.admin.sent', ['email' => $user->email]));
    }

    /**
     * @return Collection<int, Stand>
     */
    #[Computed]
    public function stands(): Collection
    {
        return Stand::query()->ordered()->get(['id', 'name']);
    }

    /**
     * "Act as": sign in as this user to check what they see and may do.
     */
    public function impersonate(int $userId, Impersonation $impersonation): void
    {
        $this->authorize('admin');

        $impersonation->start(Auth::user(), User::findOrFail($userId));

        $this->redirectRoute('dashboard');
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
            ->with(['schoolClasses', 'managedStands' => fn ($stands) => $stands->ordered()])
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
