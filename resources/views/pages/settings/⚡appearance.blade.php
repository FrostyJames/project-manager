<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">

{{-- Settings Header --}}
@include('partials.settings-heading')

<flux:heading class="sr-only">
    {{ __('Appearance settings') }}
</flux:heading>


{{-- Appearance Settings Card --}}
<x-pages::settings.layout
    :heading="__('Appearance')"
    :subheading="__('Customize how your project manager looks and feels.')"
>

    <div class="space-y-6">

        {{-- Appearance Description --}}
        <div
            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/50"
        >

            <div class="flex items-start gap-4">

                {{-- Icon --}}
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400"
                >
                    <flux:icon
                        name="paint-brush"
                        class="size-5"
                    />
                </div>


                {{-- Text --}}
                <div>

                    <flux:heading
                        size="sm"
                        class="font-semibold"
                    >
                        {{ __('Choose your appearance') }}
                    </flux:heading>

                    <flux:text class="mt-1 text-sm">
                        {{ __('Select how the application should appear on your device.') }}
                    </flux:text>

                </div>

            </div>

        </div>


        {{-- Appearance Options --}}
        <div>

            <flux:radio.group
                x-data
                variant="segmented"
                x-model="$flux.appearance"
                class="w-full"
            >

                <flux:radio
                    value="light"
                    icon="sun"
                >
                    {{ __('Light') }}
                </flux:radio>

                <flux:radio
                    value="dark"
                    icon="moon"
                >
                    {{ __('Dark') }}
                </flux:radio>

                <flux:radio
                    value="system"
                    icon="computer-desktop"
                >
                    {{ __('System') }}
                </flux:radio>

            </flux:radio.group>

        </div>


        {{-- Current Setting Information --}}
        <div
            class="flex items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 dark:border-indigo-900/50 dark:bg-indigo-950/30"
        >

            <flux:icon
                name="information-circle"
                class="mt-0.5 size-5 shrink-0 text-indigo-600 dark:text-indigo-400"
            />

            <div>

                <p class="text-sm font-medium text-indigo-900 dark:text-indigo-200">
                    {{ __('Appearance preferences') }}
                </p>

                <p class="mt-1 text-xs leading-5 text-indigo-700 dark:text-indigo-300">
                    {{ __('Your appearance preference is applied immediately and will be remembered for future visits.') }}
                </p>

            </div>

        </div>

    </div>

</x-pages::settings.layout>


</section>
