@props(['showTeam' => true])

<flux:dropdown position="bottom" align="start">


{{-- ================================================================ --}}
{{-- SIDEBAR USER BUTTON --}}
{{-- ================================================================ --}}

<button
    type="button"
    class="group flex w-full items-center rounded-xl p-2 transition hover:bg-zinc-100 dark:hover:bg-white/10"
    data-test="sidebar-menu-button"
>

    {{-- Avatar --}}
    <flux:avatar
        :initials="auth()->user()->initials()"
        size="sm"
    />

    {{-- User Information --}}
    <div
        class="in-data-flux-sidebar-collapsed-desktop:hidden mx-2 grid min-w-0 flex-1 text-start text-sm leading-tight"
    >

        <span
            class="truncate font-semibold text-zinc-800 transition group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
        >
            {{ auth()->user()->name }}
        </span>

        @if ($showTeam && auth()->user()->currentTeam)

            <span
                class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400"
            >
                {{ auth()->user()->currentTeam->name }}
            </span>

        @endif

    </div>

    {{-- Dropdown Icon --}}
    <flux:icon
        name="chevrons-up-down"
        variant="micro"
        class="in-data-flux-sidebar-collapsed-desktop:hidden ms-auto size-4 shrink-0 text-zinc-400 transition group-hover:text-indigo-600 dark:text-white/60 dark:group-hover:text-indigo-400"
    />

</button>


{{-- ================================================================ --}}
{{-- DROPDOWN MENU --}}
{{-- ================================================================ --}}

<flux:menu>

    {{-- User Information --}}
    <div class="flex items-center gap-3 px-2 py-2">

        <flux:avatar
            :name="auth()->user()->name"
            :initials="auth()->user()->initials()"
        />

        <div class="grid min-w-0 flex-1 text-start text-sm leading-tight">

            <flux:heading
                class="truncate text-sm font-semibold"
            >
                {{ auth()->user()->name }}
            </flux:heading>

            <flux:text class="truncate text-xs">
                {{ auth()->user()->email }}
            </flux:text>

        </div>

    </div>


    <flux:menu.separator />


    {{-- ============================================================ --}}
    {{-- MENU ITEMS --}}
    {{-- ============================================================ --}}

    <flux:menu.radio.group>

        {{-- Settings --}}
        <flux:menu.item
            :href="route('profile.edit')"
            icon="cog"
            wire:navigate
        >
            {{ __('Settings') }}
        </flux:menu.item>


        {{-- Logout --}}
        <form
            method="POST"
            action="{{ route('logout') }}"
            class="w-full"
        >

            @csrf

            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer"
                data-test="logout-button"
            >
                {{ __('Log out') }}
            </flux:menu.item>

        </form>

    </flux:menu.radio.group>

</flux:menu>


</flux:dropdown>
