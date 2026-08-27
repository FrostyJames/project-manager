@props([
'invitation',
'action',
])

<div
    data-test="team-invitation-alert"
    class="mb-6 overflow-hidden rounded-2xl border border-indigo-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
>
    <div class="flex items-start gap-4 p-5">

```
    {{-- Icon --}}
    <div
        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400"
    >
        <flux:icon
            name="information-circle"
            class="size-5"
        />
    </div>

    {{-- Message --}}
    <div class="min-w-0 flex-1">

        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
            Team Invitation
        </h3>

        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
            {{ __(':action to join the ":team" team.', [
                'action' => $action,
                'team' => $invitation['teamName'],
            ]) }}
        </p>

    </div>

</div>
```

</div>
