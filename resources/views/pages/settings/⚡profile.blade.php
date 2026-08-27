
<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update profile information.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate(
            $this->profileRules($user->id)
        );

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(
            variant: 'success',
            text: __('Profile updated successfully.')
        );
    }

    /**
     * Resend email verification notification.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(
                default: route('dashboard', absolute: false)
            );

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash(
            'status',
            'verification-link-sent'
        );
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail
            && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (
                Auth::user() instanceof MustVerifyEmail
                && Auth::user()->hasVerifiedEmail()
            );
    }
};
?>

<section class="w-full">

    @include('partials.settings-heading')

    <flux:heading class="sr-only">
        {{ __('Profile settings') }}
    </flux:heading>

    <x-pages::settings.layout
        :heading="__('Profile')"
        :subheading="__('Manage your personal information and account details.')"
    >

        {{-- ============================================================ --}}
        {{-- PROFILE INFORMATION --}}
        {{-- ============================================================ --}}

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            {{-- Header --}}
            <div class="border-b border-zinc-200 px-6 py-5 dark:border-zinc-700">

                <div class="flex items-center gap-4">

                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">

                        <flux:icon name="user" class="size-5" />

                    </div>

                    <div>

                        <flux:heading size="lg">
                            {{ __('Personal Information') }}
                        </flux:heading>

                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                            {{ __('Update your name and email address.') }}
                        </flux:text>

                    </div>

                </div>

            </div>


            {{-- Form --}}
            <form
                wire:submit="updateProfileInformation"
                class="space-y-6 p-6"
            >

                {{-- Name --}}
                <div>

                    <flux:input
                        wire:model="name"
                        :label="__('Name')"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                    />

                    @error('name')
                        <flux:text class="mt-2 text-sm !text-red-600">
                            {{ $message }}
                        </flux:text>
                    @enderror

                </div>


                {{-- Email --}}
                <div>

                    <flux:input
                        wire:model="email"
                        :label="__('Email address')"
                        type="email"
                        required
                        autocomplete="email"
                    />

                    @error('email')
                        <flux:text class="mt-2 text-sm !text-red-600">
                            {{ $message }}
                        </flux:text>
                    @enderror


                    {{-- Email verification --}}
                    @if ($this->hasUnverifiedEmail)

                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30">

                            <div class="flex gap-3">

                                <flux:icon
                                    name="exclamation-triangle"
                                    class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400"
                                />

                                <div class="space-y-2">

                                    <flux:text class="font-medium !text-amber-900 dark:!text-amber-200">
                                        {{ __('Your email address is not verified.') }}
                                    </flux:text>

                                    <flux:text class="!text-amber-800 dark:!text-amber-300">
                                        {{ __('Please verify your email address to keep your account secure.') }}
                                    </flux:text>

                                    <flux:link
                                        class="cursor-pointer text-sm font-medium !text-amber-700 dark:!text-amber-300"
                                        wire:click.prevent="resendVerificationNotification"
                                    >
                                        {{ __('Resend verification email') }}
                                    </flux:link>

                                    @if (session('status') === 'verification-link-sent')

                                        <div class="flex items-center gap-2 pt-1">

                                            <flux:icon
                                                name="check-circle"
                                                class="size-4 text-green-600 dark:text-green-400"
                                            />

                                            <flux:text class="font-medium !text-green-600 dark:!text-green-400">
                                                {{ __('A new verification link has been sent to your email address.') }}
                                            </flux:text>

                                        </div>

                                    @endif

                                </div>

                            </div>

                        </div>

                    @endif

                </div>


                {{-- Save --}}
                <div class="flex items-center justify-end border-t border-zinc-200 pt-5 dark:border-zinc-700">

                    <flux:button
                        variant="primary"
                        type="submit"
                        data-test="update-profile-button"
                    >
                        <flux:icon name="check" class="size-4" />

                        {{ __('Save changes') }}
                    </flux:button>

                </div>

            </form>

        </div>


        {{-- ============================================================ --}}
        {{-- DELETE ACCOUNT --}}
        {{-- ============================================================ --}}

        @if ($this->showDeleteUser)

            <div class="mt-8 overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm dark:border-red-900/50 dark:bg-zinc-900">

                <div class="border-b border-red-100 bg-red-50/50 px-6 py-5 dark:border-red-900/30 dark:bg-red-950/20">

                    <div class="flex items-center gap-4">

                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400">

                            <flux:icon name="trash" class="size-5" />

                        </div>

                        <div>

                            <flux:heading size="lg">
                                {{ __('Delete Account') }}
                            </flux:heading>

                            <flux:text class="mt-1 !text-red-600 dark:!text-red-400">
                                {{ __('Permanently remove your account and all associated data.') }}
                            </flux:text>

                        </div>

                    </div>

                </div>

                <div class="p-6">

                    <livewire:pages::settings.delete-user-form />

                </div>

            </div>

        @endif

    </x-pages::settings.layout>

</section>

