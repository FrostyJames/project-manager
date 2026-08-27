
<?php

use App\Models\Task;
use Livewire\Component;

new class extends Component
{
    public bool $showCreateForm = false;

    public bool $showEditForm = false;

    public ?int $editingTaskId = null;

    public string $title = '';

    public string $description = '';

    public ?int $project_id = null;

    public ?int $assigned_to = null;

    public string $status = 'todo';

    public string $priority = 'medium';

    public string $due_date = '';

    public function with(): array
    {
        $team = auth()->user()->currentTeam;

        return [
            'projects' => $team
                ? $team->projects()->latest()->get()
                : collect(),

            'members' => $team
                ? $team->members()->orderBy('name')->get()
                : collect(),

            'tasks' => $team
                ? $team->projects()
                    ->with([
                        'tasks.assignee',
                        'tasks.project',
                    ])
                    ->get()
                    ->flatMap->tasks
                    ->sortByDesc('created_at')
                    ->values()
                : collect(),
        ];
    }

    public function createTask(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project_id' => ['required', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'status' => ['required', 'in:todo,in_progress,completed'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ]);

        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $project = $team->projects()->findOrFail($this->project_id);

        if ($this->assigned_to) {
            $team->members()->findOrFail($this->assigned_to);
        }

        $project->tasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'assigned_to' => $validated['assigned_to'],
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'],
        ]);

        $this->reset([
            'title',
            'description',
            'project_id',
            'assigned_to',
            'due_date',
        ]);

        $this->status = 'todo';
        $this->priority = 'medium';
        $this->showCreateForm = false;

        session()->flash(
            'success',
            'Task created successfully.'
        );
    }

    public function editTask(Task $task): void
    {
        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        abort_unless(
            $team->projects()
                ->whereKey($task->project_id)
                ->exists(),
            403
        );

        $this->editingTaskId = $task->id;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->project_id = $task->project_id;
        $this->assigned_to = $task->assigned_to;
        $this->status = $task->status;
        $this->priority = $task->priority;

        $this->due_date = $task->due_date
            ? $task->due_date->format('Y-m-d')
            : '';

        $this->showEditForm = true;
        $this->showCreateForm = false;
    }

    public function updateTask(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project_id' => ['required', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'status' => ['required', 'in:todo,in_progress,completed'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ]);

        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $project = $team->projects()->findOrFail(
            $this->project_id
        );

        if ($this->assigned_to) {
            $team->members()->findOrFail($this->assigned_to);
        }

        $task = $project->tasks()->findOrFail(
            $this->editingTaskId
        );

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'project_id' => $project->id,
            'assigned_to' => $validated['assigned_to'],
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'],
        ]);

        $this->reset([
            'title',
            'description',
            'project_id',
            'assigned_to',
            'due_date',
            'editingTaskId',
        ]);

        $this->status = 'todo';
        $this->priority = 'medium';
        $this->showEditForm = false;

        session()->flash(
            'success',
            'Task updated successfully.'
        );
    }

    public function deleteTask(int $taskId): void
    {
        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $task = Task::whereHas('project', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })->findOrFail($taskId);

        $task->delete();

        session()->flash(
            'success',
            'Task deleted successfully.'
        );
    }
};
?>

<div class="min-h-full">

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
                                d="M9 5.25h6M9 9.75h6M9 14.25h6M9 18.75h6M6.75 5.25h.008v.008H6.75V5.25zm0 4.5h.008v.008H6.75V9.75zm0 4.5h.008v.008H6.75v-.008zm0 4.5h.008v.008H6.75v-.008z"
                            />
                        </svg>
                    </div>

                    <div>

                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Tasks
                        </h1>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Manage and track tasks across your team's projects.
                        </p>

                    </div>

                </div>

            </div>

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

                New Task
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
    {{-- CREATE TASK FORM --}}
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
                        Create New Task
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Add a task to one of your team's projects.
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
                wire:submit="createTask"
                class="space-y-6 p-6"
            >

                <div class="grid gap-5 md:grid-cols-2">

                    {{-- Project --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Project
                        </label>

                        <select
                            wire:model="project_id"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                            <option value="">
                                Select a project
                            </option>

                            @foreach ($projects as $project)

                                <option value="{{ $project->id }}">
                                    {{ $project->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('project_id')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Assigned To --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Assign To
                        </label>

                        <select
                            wire:model="assigned_to"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                            <option value="">
                                Unassigned
                            </option>

                            @foreach ($members as $member)

                                <option value="{{ $member->id }}">
                                    {{ $member->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('assigned_to')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>


                {{-- Title --}}
                <div>

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Task Title
                    </label>

                    <input
                        type="text"
                        wire:model="title"
                        placeholder="e.g. Design the login page"
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    >

                    @error('title')
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
                        placeholder="Describe what needs to be done..."
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    ></textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="grid gap-5 md:grid-cols-3">

                    {{-- Status --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Status
                        </label>

                        <select
                            wire:model="status"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="todo">
                                To Do
                            </option>

                            <option value="in_progress">
                                In Progress
                            </option>

                            <option value="completed">
                                Completed
                            </option>
                        </select>

                        @error('status')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Priority --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Priority
                        </label>

                        <select
                            wire:model="priority"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="low">
                                Low
                            </option>

                            <option value="medium">
                                Medium
                            </option>

                            <option value="high">
                                High
                            </option>
                        </select>

                        @error('priority')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Due Date --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Due Date
                        </label>

                        <input
                            type="date"
                            wire:model="due_date"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        @error('due_date')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>


                {{-- Buttons --}}
                <div
                    class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"
                >

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
                        Create Task
                    </button>

                </div>

            </form>

        </div>

    @endif


    {{-- ================================================================ --}}
    {{-- EDIT TASK FORM --}}
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
                        Edit Task
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Update the task information below.
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
                wire:submit="updateTask"
                class="space-y-6 p-6"
            >

                <div class="grid gap-5 md:grid-cols-2">

                    {{-- Project --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Project
                        </label>

                        <select
                            wire:model="project_id"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                            @foreach ($projects as $project)

                                <option value="{{ $project->id }}">
                                    {{ $project->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('project_id')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Assigned To --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Assign To
                        </label>

                        <select
                            wire:model="assigned_to"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                            <option value="">
                                Unassigned
                            </option>

                            @foreach ($members as $member)

                                <option value="{{ $member->id }}">
                                    {{ $member->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('assigned_to')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>


                {{-- Title --}}
                <div>

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Task Title
                    </label>

                    <input
                        type="text"
                        wire:model="title"
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                    @error('title')
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
                        class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="grid gap-5 md:grid-cols-3">

                    {{-- Status --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Status
                        </label>

                        <select
                            wire:model="status"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="todo">
                                To Do
                            </option>

                            <option value="in_progress">
                                In Progress
                            </option>

                            <option value="completed">
                                Completed
                            </option>
                        </select>

                        @error('status')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Priority --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Priority
                        </label>

                        <select
                            wire:model="priority"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="low">
                                Low
                            </option>

                            <option value="medium">
                                Medium
                            </option>

                            <option value="high">
                                High
                            </option>
                        </select>

                        @error('priority')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Due Date --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Due Date
                        </label>

                        <input
                            type="date"
                            wire:model="due_date"
                            class="w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        @error('due_date')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>


                {{-- Buttons --}}
                <div
                    class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"
                >

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
    {{-- TASK LIST --}}
    {{-- ================================================================ --}}

    @if ($tasks->count())

        <div class="mb-5 flex items-center justify-between">

            <div>

                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Your Tasks
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $tasks->count() }}
                    {{ $tasks->count() === 1 ? 'task' : 'tasks' }}
                    across your projects.
                </p>

            </div>

        </div>


        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">

            @foreach ($tasks as $task)

                @php

                    $statusClasses = match ($task->status) {

                        'completed' =>
                            'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400',

                        'in_progress' =>
                            'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400',

                        default =>
                            'bg-gray-100 text-gray-700 ring-gray-500/20 dark:bg-gray-800 dark:text-gray-300',

                    };

                    $priorityClasses = match ($task->priority) {

                        'high' =>
                            'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400',

                        'medium' =>
                            'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400',

                        default =>
                            'bg-gray-100 text-gray-600 ring-gray-500/20 dark:bg-gray-800 dark:text-gray-400',

                    };

                @endphp


                <div
                    wire:key="task-{{ $task->id }}"
                    class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-900"
                >

                    {{-- Card Content --}}
                    <div class="p-5">

                        <div class="flex items-start justify-between gap-3">

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
                                        d="M9 5.25h6M9 9.75h6M9 14.25h6M9 18.75h6M6.75 5.25h.008v.008H6.75V5.25zm0 4.5h.008v.008H6.75V9.75zm0 4.5h.008v.008H6.75V9.75zm0 4.5h.008v.008H6.75v-.008z"
                                    />
                                </svg>

                            </div>


                            <span
                                class="{{ $statusClasses }} inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                            >
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>

                        </div>


                        <h3 class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $task->title }}
                        </h3>


                        <p class="mt-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            {{ $task->project->name }}
                        </p>


                        <div class="mt-3 min-h-[42px]">

                            @if ($task->description)

                                <p class="text-sm leading-5 text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::limit($task->description, 100) }}
                                </p>

                            @else

                                <p class="text-sm italic text-gray-400 dark:text-gray-500">
                                    No description provided.
                                </p>

                            @endif

                        </div>


                        {{-- Priority --}}
                        <div class="mt-5">

                            <span
                                class="{{ $priorityClasses }} inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                            >
                                {{ ucfirst($task->priority) }} Priority
                            </span>

                        </div>


                        {{-- Task Information --}}
                        <div class="mt-5 space-y-3">

                            {{-- Assignee --}}
                            <div class="flex items-center gap-3">

                                <div
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
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
                                            d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"
                                        />
                                    </svg>
                                </div>

                                <div class="min-w-0">

                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        Assigned to
                                    </p>

                                    <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-300">

                                        @if ($task->assignee)

                                            {{ $task->assignee->name }}

                                        @else

                                            Unassigned

                                        @endif

                                    </p>

                                </div>

                            </div>


                            {{-- Due Date --}}
                            <div class="flex items-center gap-3">

                                <div
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
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
                                            d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M5.25 5.25h13.5A1.5 1.5 0 0120.25 6.75v12A1.5 1.5 0 0118.75 20.25H5.25a1.5 1.5 0 01-1.5-1.5v-12a1.5 1.5 0 011.5-1.5z"
                                        />
                                    </svg>
                                </div>

                                <div>

                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        Due date
                                    </p>

                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">

                                        @if ($task->due_date)

                                            {{ $task->due_date->format('M d, Y') }}

                                        @else

                                            No due date

                                        @endif

                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- Card Footer --}}
                    <div
                        class="mt-auto flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-5 py-4 dark:border-gray-800 dark:bg-gray-800/30"
                    >

                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            Task #{{ $task->id }}
                        </span>


                        <div class="flex items-center gap-1">

                            {{-- Edit --}}
                            <button
                                type="button"
                                wire:click="editTask({{ $task->id }})"
                                title="Edit task"
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
                                        d="M16.862 4.487a2.25 2.25 0 113.182 3.182L8.25 19.445l-4.5 1.125 1.125-4.5L16.862 4.487z"
                                    />
                                </svg>
                            </button>


                            {{-- Delete --}}
                            <button
                                type="button"
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="Are you sure you want to delete this task?"
                                title="Delete task"
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

        {{-- ============================================================ --}}
        {{-- EMPTY STATE --}}
        {{-- ============================================================ --}}

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
                        d="M9 5.25h6M9 9.75h6M9 14.25h6M9 18.75h6M6.75 5.25h.008v.008H6.75V5.25zm0 4.5h.008v.008H6.75V9.75zm0 4.5h.008v.008H6.75V9.75zm0 4.5h.008v.008H6.75v-.008z"
                    />
                </svg>

            </div>


            <h2 class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">
                No tasks yet
            </h2>


            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Get started by creating your first task. Assign it to a project
                and team member, then track its progress.
            </p>


            @if ($projects->count())

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

                    Create Your First Task

                </button>

            @else

                <div class="mx-auto mt-6 max-w-md rounded-xl bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-300">
                    Create a project first before adding tasks.
                </div>

            @endif

        </div>

    @endif

</div>

