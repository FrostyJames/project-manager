
<div class="flex w-full flex-col gap-8 lg:flex-row">

    {{-- ============================================================ --}}
    {{-- SETTINGS NAVIGATION --}}
    {{-- ============================================================ --}}

    <aside class="w-full shrink-0 lg:w-[230px]">

        <div class="rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <div class="px-3 pb-2 pt-3">

                <div class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                    {{ __('Settings') }}
                </div>

            </div>


            <flux:navlist aria-label="{{ __('Settings') }}">

                {{-- Profile --}}
                <flux:navlist.item
                    :href="route('profile.edit')"
                    :current="request()->routeIs('profile.*')"
                    icon="user"
                    wire:navigate
                >
                    {{ __('Profile') }}
                </flux:navlist.item>


                {{-- Security --}}
                <flux:navlist.item
                    :href="route('security.edit')"
                    :current="request()->routeIs('security.*')"
                    icon="shield-check"
                    wire:navigate
                >
                    {{ __('Security') }}
                </flux:navlist.item>


                {{-- Teams --}}
                <flux:navlist.item
                    :href="route('teams.index')"
                    :current="request()->routeIs('teams.*')"
                    icon="user-group"
                    wire:navigate
                >
                    {{ __('Teams') }}
                </flux:navlist.item>


                {{-- Appearance --}}
                <flux:navlist.item
                    :href="route('appearance.edit')"
                    :current="request()->routeIs('appearance.*')"
                    icon="paint-brush"
                    wire:navigate
                >
                    {{ __('Appearance') }}
                </flux:navlist.item>

            </flux:navlist>

        </div>

    </aside>


    {{-- ============================================================ --}}
    {{-- SETTINGS CONTENT --}}
    {{-- ============================================================ --}}

    <main class="min-w-0 flex-1">

        {{-- Page heading --}}
        <div class="mb-6">

            <flux:heading size="xl">
                {{ $heading ?? '' }}
            </flux:heading>

            @if ($subheading ?? false)

                <flux:subheading class="mt-1">
                    {{ $subheading }}
                </flux:subheading>

            @endif

        </div>


        {{-- Content card --}}
        <div class="w-full max-w-3xl">

            {{ $slot }}

        </div>

    </main>

</div>

