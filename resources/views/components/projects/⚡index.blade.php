
<?php

use App\Models\Project;
use Livewire\Component;

new class extends Component
{
    public bool $showCreateForm = false;

    public bool $showEditForm = false;

    public ?int $editingProjectId = null;

    public string $name = '';

    public string $description = '';

    public string $status = 'planning';

    public string $start_date = '';

    public string $end_date = '';

    public function with(): array
    {
        $team = auth()->user()->currentTeam;

        return [
            'projects' => $team
                ? $team->projects()
                    ->withCount('tasks')
                    ->with([
                        'tasks' => function ($query) {
                            $query->select(
                                'id',
                                'project_id',
                                'status'
                            );
                        },
                    ])
                    ->latest()
                    ->get()
                : collect(),
        ];
    }

    public function createProject(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:planning,active,completed,on_hold'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $team->projects()->create($validated);

        $this->reset([
            'name',
            'description',
            'start_date',
            'end_date',
        ]);

        $this->status = 'planning';

        $this->showCreateForm = false;

        session()->flash(
            'success',
            'Project created successfully.'
        );
    }

    public function editProject(Project $project): void
    {
        abort_unless(
            $project->team_id === auth()->user()->currentTeam?->id,
            403
        );

        $this->editingProjectId = $project->id;

        $this->name = $project->name;

        $this->description = $project->description ?? '';

        $this->status = $project->status;

        $this->start_date = $project->start_date
            ? $project->start_date->format('Y-m-d')
            : '';

        $this->end_date = $project->end_date
            ? $project->end_date->format('Y-m-d')
            : '';

        $this->showEditForm = true;

        $this->showCreateForm = false;
    }

    public function updateProject(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:planning,active,completed,on_hold'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $project = $team->projects()->findOrFail(
            $this->editingProjectId
        );

        $project->update($validated);

        $this->reset([
            'name',
            'description',
            'start_date',
            'end_date',
            'editingProjectId',
        ]);

        $this->status = 'planning';

        $this->showEditForm = false;

        session()->flash(
            'success',
            'Project updated successfully.'
        );
    }

    public function deleteProject(int $projectId): void
    {
        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $project = $team->projects()->findOrFail($projectId);

        $project->delete();

        session()->flash(
            'success',
            'Project deleted successfully.'
        );
    }
};
?>

<div class="min-h-full w-full">

    {{-- ================================================================ --}}
    {{-- PAGE HEADER --}}
    {{-- ================================================================ --}}

    <div class="mb-8">

        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="flex items-center gap-3">

                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.75 7.5h4.5l1.5 2.25h10.5a1.5 1.5 0 011.5 1.5v7.5a1.5 1.5 0 01-1.5 1.5h-16.5a1.5 1.5 0 01-1.5-1.5v-10.5a1.5 1.5 0 011.5-1.5z"
                            />
                        </svg>
                    </div>

                    <div>

                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Projects
                        </h1>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Manage and track your team's projects.
                        </p>

                    </div>

                </div>

            </div>


            {{-- New Project Button --}}

            <button
                type="button"
                wire:click="$set('showCreateForm', true)"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:hover:bg-indigo-500"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4.5v15m7.5-7.5h-15"
                    />
                </svg>

                New Project

            </button>

        </div>

    </div>


    {{-- ================================================================ --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ================================================================ --}}

    @if (session('success'))

        <div
            class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300"
        >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mt-0.5 size-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>

            <div class="text-sm font-medium">
                {{ session('success') }}
            </div>

        </div>

    @endif


    {{-- ================================================================ --}}
    {{-- CREATE PROJECT FORM --}}
    {{-- ================================================================ --}}

    @if ($showCreateForm)

        <div
            class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >

            <div
                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-5 dark:border-gray-700 dark:bg-gray-800/50"
            >

                <div>

                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Create New Project
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Add a new project to your current team.
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="$set('showCreateForm', false)"
                    class="flex size-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-white"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="size-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>

                </button>

            </div>


            <form
                wire:submit="createProject"
                class="space-y-6 p-6"
            >

                {{-- Name --}}

                <div>

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Project Name
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="e.g. Website Redesign"
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    >

                    @error('name')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Description --}}

                <div>

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Description
                    </label>

                    <textarea
                        wire:model="description"
                        rows="4"
                        placeholder="Describe what this project is about..."
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    ></textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Status --}}

                <div>

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Status
                    </label>

                    <select
                        wire:model="status"
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                        <option value="planning">
                            Planning
                        </option>

                        <option value="active">
                            Active
                        </option>

                        <option value="completed">
                            Completed
                        </option>

                        <option value="on_hold">
                            On Hold
                        </option>

                    </select>

                    @error('status')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Dates --}}

                <div class="grid gap-5 md:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Start Date
                        </label>

                        <input
                            type="date"
                            wire:model="start_date"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        @error('start_date')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            End Date
                        </label>

                        <input
                            type="date"
                            wire:model="end_date"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        @error('end_date')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>


                {{-- Buttons --}}

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800">

                    <button
                        type="button"
                        wire:click="$set('showCreateForm', false)"
                        class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 dark:hover:bg-indigo-500"
                    >
                        Create Project
                    </button>

                </div>

            </form>

        </div>

    @endif


    {{-- ================================================================ --}}
    {{-- EDIT PROJECT FORM --}}
    {{-- ================================================================ --}}

    @if ($showEditForm)

        <div
            class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >

            <div
                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-5 dark:border-gray-700 dark:bg-gray-800/50"
            >

                <div>

                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Edit Project
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Update your project's information.
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="$set('showEditForm', false)"
                    class="flex size-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-white"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="size-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>

                </button>

            </div>


            <form
                wire:submit="updateProject"
                class="space-y-6 p-6"
            >

                <div>

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Project Name
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                    @error('name')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Description
                    </label>

                    <textarea
                        wire:model="description"
                        rows="4"
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Status
                    </label>

                    <select
                        wire:model="status"
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                        <option value="planning">
                            Planning
                        </option>

                        <option value="active">
                            Active
                        </option>

                        <option value="completed">
                            Completed
                        </option>

                        <option value="on_hold">
                            On Hold
                        </option>

                    </select>

                    @error('status')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="grid gap-5 md:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Start Date
                        </label>

                        <input
                            type="date"
                            wire:model="start_date"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        @error('start_date')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            End Date
                        </label>

                        <input
                            type="date"
                            wire:model="end_date"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        @error('end_date')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>


                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800">

                    <button
                        type="button"
                        wire:click="$set('showEditForm', false)"
                        class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 dark:hover:bg-indigo-500"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    @endif


    {{-- ================================================================ --}}
    {{-- PROJECT LIST --}}
    {{-- ================================================================ --}}

    @if ($projects->count())

        <div class="mb-5">

            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Your Projects
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">

                {{ $projects->count() }}

                {{ $projects->count() === 1 ? 'project' : 'projects' }}

                in your current team.

            </p>

        </div>


        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">

            @foreach ($projects as $project)

                @php

                    $taskCount = $project->tasks_count;

                    $completedCount = $project->tasks
                        ->where('status', 'completed')
                        ->count();

                    $progress = $taskCount > 0
                        ? round(($completedCount / $taskCount) * 100)
                        : 0;

                    $statusClasses = match ($project->status) {

                        'active' =>
                            'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400',

                        'completed' =>
                            'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400',

                        'on_hold' =>
                            'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400',

                        default =>
                            'bg-gray-100 text-gray-700 ring-gray-500/20 dark:bg-gray-800 dark:text-gray-300',

                    };

                    $progressBar = match ($project->status) {

                        'completed' => 'bg-green-500',

                        'on_hold' => 'bg-yellow-500',

                        'active' => 'bg-blue-500',

                        default => 'bg-indigo-500',

                    };

                @endphp


                <div
                    wire:key="project-{{ $project->id }}"
                    class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-900"
                >

                    <div class="p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="size-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.75 7.5h4.5l1.5 2.25h10.5a1.5 1.5 0 011.5 1.5v7.5a1.5 1.5 0 01-1.5 1.5h-16.5a1.5 1.5 0 01-1.5-1.5v-10.5a1.5 1.5 0 011.5-1.5z"
                                    />
                                </svg>

                            </div>


                            <span
                                class="{{ $statusClasses }} inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                            >
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>

                        </div>


                        <h3 class="mt-5 truncate text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $project->name }}
                        </h3>


                        <p class="mt-2 min-h-[40px] text-sm leading-5 text-gray-500 dark:text-gray-400">

                            @if ($project->description)

                                {{ \Illuminate\Support\Str::limit($project->description, 100) }}

                            @else

                                No description provided.

                            @endif

                        </p>


                        {{-- Progress --}}

                        <div class="mt-6">

                            <div class="mb-2 flex items-center justify-between">

                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Progress
                                </span>

                                <span class="text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ $progress }}%
                                </span>

                            </div>


                            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">

                                <div
                                    class="{{ $progressBar }} h-full rounded-full transition-all duration-500"
                                    style="width: {{ $progress }}%"
                                ></div>

                            </div>

                        </div>


                        {{-- Information --}}

                        <div class="mt-6 grid grid-cols-2 gap-3">

                            <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800/60">

                                <div class="flex items-center gap-2">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="size-4 text-gray-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 5.25h6M9 9.75h6M9 14.25h6M9 18.75h6M6.75 5.25h.008v.008H6.75V5.25zm0 4.5h.008v.008H6.75V9.75zm0 4.5h.008v.008H6.75v-.008zm0 4.5h.008v.008H6.75v-.008z"
                                        />
                                    </svg>

                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Tasks
                                    </span>

                                </div>

                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $taskCount }}
                                </p>

                            </div>


                            <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800/60">

                                <div class="flex items-center gap-2">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="size-4 text-gray-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M5.25 5.25h13.5A1.5 1.5 0 0120.25 6.75v12A1.5 1.5 0 0118.75 20.25H5.25a1.5 1.5 0 01-1.5-1.5v-12a1.5 1.5 0 011.5-1.5z"
                                        />
                                    </svg>

                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Timeline
                                    </span>

                                </div>

                                <p class="mt-1 truncate text-sm font-semibold text-gray-900 dark:text-white">

                                    @if ($project->start_date && $project->end_date)

                                        {{ $project->start_date->format('M d') }}
                                        -
                                        {{ $project->end_date->format('M d') }}

                                    @elseif ($project->start_date)

                                        From {{ $project->start_date->format('M d, Y') }}

                                    @elseif ($project->end_date)

                                        Until {{ $project->end_date->format('M d, Y') }}

                                    @else

                                        No dates

                                    @endif

                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- Footer --}}

                    <div
                        class="mt-auto flex items-center justify-end border-t border-gray-100 bg-gray-50/70 px-5 py-4 dark:border-gray-800 dark:bg-gray-800/30"
                    >

                        <div class="flex items-center gap-1">

                            <button
                                type="button"
                                wire:click="editProject({{ $project->id }})"
                                title="Edit project"
                                class="flex size-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="size-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16.862 4.487a2.25 2.25 0 113.182 3.182L8.25 19.445l-4.5 1.125 1.125-4.5 10.612-10.613z"
                                    />
                                </svg>

                            </button>


                            <button
                                type="button"
                                wire:click="deleteProject({{ $project->id }})"
                                wire:confirm="Are you sure you want to delete this project?"
                                title="Delete project"
                                class="flex size-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:text-gray-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                            >

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="size-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 7.5h12M9.75 7.5V5.25h4.5V7.5m-6.75 0l.75 12h7.5l.75-12M10.5 11.25v5.25m3-5.25v5.25"
                                    />
                                </svg>

                            </button>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    @else

        {{-- Empty State --}}

        <div
            class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900"
        >

            <div
                class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-8"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3.75 7.5h4.5l1.5 2.25h10.5a1.5 1.5 0 011.5 1.5v7.5a1.5 1.5 0 01-1.5 1.5h-16.5a1.5 1.5 0 01-1.5-1.5v-10.5a1.5 1.5 0 011.5-1.5z"
                    />
                </svg>

            </div>


            <h2 class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">
                No projects yet
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Get started by creating your first project. You can then add tasks,
                assign team members, and track progress.
            </p>


            <button
                type="button"
                wire:click="$set('showCreateForm', true)"
                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 dark:hover:bg-indigo-500"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4.5v15m7.5-7.5h-15"
                    />
                </svg>

                Create Your First Project

            </button>

        </div>

    @endif

</div>
