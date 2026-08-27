
<?php

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    /*
    |--------------------------------------------------------------------------
    | Search & Filters
    |--------------------------------------------------------------------------
    */

    public string $search = '';

    public string $filterStatus = '';

    public string $filterPriority = '';

    public string $filterAssignee = '';

    /*
    |--------------------------------------------------------------------------
    | Edit Task
    |--------------------------------------------------------------------------
    */

    public bool $showEditForm = false;

    public ?int $editingTaskId = null;

    public string $title = '';

    public string $description = '';

    public ?int $assigned_to = null;

    public string $status = 'todo';

    public string $priority = 'medium';

    public string $due_date = '';

    /*
    |--------------------------------------------------------------------------
    | Comments
    |--------------------------------------------------------------------------
    */

    public array $commentText = [];

    public ?int $editingCommentId = null;

    public string $editingCommentText = '';

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(Project $project): void
    {
        abort_unless(
            $project->team_id === auth()->user()->currentTeam?->id,
            403
        );

        $this->project = $project;
    }

    /*
    |--------------------------------------------------------------------------
    | Load Tasks & Members
    |--------------------------------------------------------------------------
    */

    public function with(): array
    {
        $query = $this->project
            ->tasks()
            ->with([
                'assignee',
                'comments.user',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (trim($this->search) !== '') {
            $search = trim($this->search);

            $query->where(function ($query) use ($search) {
                $query
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere(
                        'description',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($this->filterStatus !== '') {
            $query->where(
                'status',
                $this->filterStatus
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Priority Filter
        |--------------------------------------------------------------------------
        */

        if ($this->filterPriority !== '') {
            $query->where(
                'priority',
                $this->filterPriority
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Assignee Filter
        |--------------------------------------------------------------------------
        */

        if ($this->filterAssignee !== '') {
            $query->where(
                'assigned_to',
                $this->filterAssignee
            );
        }

        return [
            'tasks' => $query
                ->latest()
                ->get(),

            'members' => $this->project
                ->team
                ->members()
                ->orderBy('name')
                ->get(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Filters
    |--------------------------------------------------------------------------
    */

    public function clearFilters(): void
    {
        $this->search = '';

        $this->filterStatus = '';

        $this->filterPriority = '';

        $this->filterAssignee = '';
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Task
    |--------------------------------------------------------------------------
    */

    public function editTask(Task $task): void
    {
        abort_unless(
            $task->project_id === $this->project->id,
            403
        );

        $this->editingTaskId = $task->id;

        $this->title = $task->title;

        $this->description = $task->description ?? '';

        $this->assigned_to = $task->assigned_to;

        $this->status = $task->status;

        $this->priority = $task->priority;

        $this->due_date = $task->due_date
            ? $task->due_date->format('Y-m-d')
            : '';

        $this->showEditForm = true;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Task
    |--------------------------------------------------------------------------
    */

    public function updateTask(): void
    {
        $validated = $this->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'assigned_to' => [
                'nullable',
                'integer',
            ],

            'status' => [
                'required',
                'in:todo,in_progress,completed',
            ],

            'priority' => [
                'required',
                'in:low,medium,high',
            ],

            'due_date' => [
                'nullable',
                'date',
            ],
        ]);

        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $task = $this->project
            ->tasks()
            ->findOrFail($this->editingTaskId);

        if ($this->assigned_to) {
            $team->members()->findOrFail(
                $this->assigned_to
            );
        }

        $task->update([
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

    /*
    |--------------------------------------------------------------------------
    | Delete Task
    |--------------------------------------------------------------------------
    */

    public function deleteTask(int $taskId): void
    {
        $task = $this->project
            ->tasks()
            ->findOrFail($taskId);

        $task->delete();

        session()->flash(
            'success',
            'Task deleted successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Add Comment
    |--------------------------------------------------------------------------
    */

    public function addComment(int $taskId): void
    {
        $task = $this->project
            ->tasks()
            ->findOrFail($taskId);

        $text = trim(
            $this->commentText[$taskId] ?? ''
        );

        if ($text === '') {
            $this->addError(
                "commentText.$taskId",
                'Comment cannot be empty.'
            );

            return;
        }

        if (strlen($text) > 2000) {
            $this->addError(
                "commentText.$taskId",
                'Comment cannot be longer than 2000 characters.'
            );

            return;
        }

        $task->comments()->create([
            'user_id' => auth()->id(),
            'body' => $text,
        ]);

        $this->commentText[$taskId] = '';

        $this->resetErrorBag(
            "commentText.$taskId"
        );

        session()->flash(
            'success',
            'Comment added successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Comment
    |--------------------------------------------------------------------------
    */

    public function editComment(int $commentId): void
    {
        $comment = Comment::whereHas(
            'task',
            function ($query) {
                $query->where(
                    'project_id',
                    $this->project->id
                );
            }
        )->findOrFail($commentId);

        abort_unless(
            $comment->user_id === auth()->id(),
            403
        );

        $this->editingCommentId = $comment->id;

        $this->editingCommentText = $comment->body;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Comment
    |--------------------------------------------------------------------------
    */

    public function updateComment(): void
    {
        $validated = $this->validate([
            'editingCommentText' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $comment = Comment::whereHas(
            'task',
            function ($query) {
                $query->where(
                    'project_id',
                    $this->project->id
                );
            }
        )->findOrFail($this->editingCommentId);

        abort_unless(
            $comment->user_id === auth()->id(),
            403
        );

        $comment->update([
            'body' => trim(
                $validated['editingCommentText']
            ),
        ]);

        $this->editingCommentId = null;

        $this->editingCommentText = '';

        session()->flash(
            'success',
            'Comment updated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Comment Edit
    |--------------------------------------------------------------------------
    */

    public function cancelCommentEdit(): void
    {
        $this->editingCommentId = null;

        $this->editingCommentText = '';

        $this->resetValidation(
            'editingCommentText'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Comment
    |--------------------------------------------------------------------------
    */

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::whereHas(
            'task',
            function ($query) {
                $query->where(
                    'project_id',
                    $this->project->id
                );
            }
        )->findOrFail($commentId);

        abort_unless(
            $comment->user_id === auth()->id(),
            403
        );

        $comment->delete();

        if ($this->editingCommentId === $commentId) {
            $this->editingCommentId = null;

            $this->editingCommentText = '';
        }

        session()->flash(
            'success',
            'Comment deleted successfully.'
        );
    }
};
?>

<div class="p-6">

    {{-- Header --}}
    <div class="mb-6">

        <div class="flex items-center gap-3">

            <a
                href="{{ route('projects.index', [
                    'current_team' => auth()->user()->currentTeam->slug
                ]) }}"
                class="text-gray-500 hover:text-black"
            >
                ← Projects
            </a>

        </div>

        <div class="mt-4">

            <h1 class="text-2xl font-bold">
                {{ $project->name }}
            </h1>

            @if ($project->description)

                <p class="mt-2 text-gray-600">
                    {{ $project->description }}
                </p>

            @endif

        </div>

    </div>


    {{-- Success Message --}}
    @if (session('success'))

        <div class="mb-6 p-4 bg-green-100 text-green-800 rounded-lg">
            {{ session('success') }}
        </div>

    @endif


    {{-- Project Information --}}
    <div class="mb-6 p-5 bg-white border rounded-xl shadow-sm">

        <div class="flex flex-wrap gap-6 text-sm text-gray-600">

            <div>

                <span class="font-medium text-gray-900">
                    Status:
                </span>

                {{ ucfirst(str_replace('_', ' ', $project->status)) }}

            </div>

            <div>

                <span class="font-medium text-gray-900">
                    Tasks:
                </span>

                {{ $tasks->count() }}

            </div>

            @if ($project->start_date)

                <div>

                    <span class="font-medium text-gray-900">
                        Start:
                    </span>

                    {{ $project->start_date->format('M d, Y') }}

                </div>

            @endif

            @if ($project->end_date)

                <div>

                    <span class="font-medium text-gray-900">
                        End:
                    </span>

                    {{ $project->end_date->format('M d, Y') }}

                </div>

            @endif

        </div>

    </div>


    {{-- Tasks Header --}}
    <div class="flex items-center justify-between mb-6">

        <div>

            <h2 class="text-xl font-semibold">
                Tasks
            </h2>

            <p class="text-sm text-gray-600">
                Tasks belonging to this project.
            </p>

        </div>

        <a
            href="{{ route('tasks.index', [
                'current_team' => auth()->user()->currentTeam->slug
            ]) }}"
            class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800"
        >
            + New Task
        </a>

    </div>


    {{-- Search & Filters --}}
    <div class="mb-8 p-5 bg-white border rounded-xl shadow-sm">

        <div class="flex items-center justify-between mb-4">

            <div>

                <h2 class="font-semibold">
                    Search & Filter Tasks
                </h2>

                <p class="text-sm text-gray-500">
                    Find tasks quickly using the options below.
                </p>

            </div>

            @if (
                $search !== '' ||
                $filterStatus !== '' ||
                $filterPriority !== '' ||
                $filterAssignee !== ''
            )

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="text-sm text-red-600 hover:text-red-800"
                >
                    Clear Filters
                </button>

            @endif

        </div>


        {{-- Search --}}
        <div class="mb-4">

            <label
                for="task-search"
                class="block mb-1 text-sm font-medium"
            >
                Search
            </label>

            <input
                id="task-search"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by task title or description..."
                class="w-full rounded-lg border-gray-300"
            >

        </div>


        {{-- Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- Status --}}
            <div>

                <label
                    for="task-status-filter"
                    class="block mb-1 text-sm font-medium"
                >
                    Status
                </label>

                <select
                    id="task-status-filter"
                    wire:model.live="filterStatus"
                    class="w-full rounded-lg border-gray-300"
                >

                    <option value="">
                        All Statuses
                    </option>

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

            </div>


            {{-- Priority --}}
            <div>

                <label
                    for="task-priority-filter"
                    class="block mb-1 text-sm font-medium"
                >
                    Priority
                </label>

                <select
                    id="task-priority-filter"
                    wire:model.live="filterPriority"
                    class="w-full rounded-lg border-gray-300"
                >

                    <option value="">
                        All Priorities
                    </option>

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

            </div>


            {{-- Assignee --}}
            <div>

                <label
                    for="task-assignee-filter"
                    class="block mb-1 text-sm font-medium"
                >
                    Assigned To
                </label>

                <select
                    id="task-assignee-filter"
                    wire:model.live="filterAssignee"
                    class="w-full rounded-lg border-gray-300"
                >

                    <option value="">
                        Everyone
                    </option>

                    @foreach ($members as $member)

                        <option value="{{ $member->id }}">
                            {{ $member->name }}
                        </option>

                    @endforeach

                </select>

            </div>

        </div>

    </div>


    {{-- Edit Task Form --}}
    @if ($showEditForm)

        <div class="mb-8 p-6 bg-white border rounded-xl shadow-sm">

            <div class="flex items-center justify-between mb-6">

                <h2 class="text-xl font-semibold">
                    Edit Task
                </h2>

                <button
                    type="button"
                    wire:click="$set('showEditForm', false)"
                    class="text-gray-500 hover:text-black"
                >
                    ✕
                </button>

            </div>

            <form
                wire:submit="updateTask"
                class="space-y-5"
            >

                {{-- Assigned To --}}
                <div>

                    <label class="block mb-1 font-medium">
                        Assign To
                    </label>

                    <select
                        wire:model="assigned_to"
                        class="w-full rounded-lg border-gray-300"
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

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- Title --}}
                <div>

                    <label class="block mb-1 font-medium">
                        Task Title
                    </label>

                    <input
                        type="text"
                        wire:model="title"
                        class="w-full rounded-lg border-gray-300"
                    >

                    @error('title')

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- Description --}}
                <div>

                    <label class="block mb-1 font-medium">
                        Description
                    </label>

                    <textarea
                        wire:model="description"
                        rows="4"
                        class="w-full rounded-lg border-gray-300"
                    ></textarea>

                    @error('description')

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- Status --}}
                <div>

                    <label class="block mb-1 font-medium">
                        Status
                    </label>

                    <select
                        wire:model="status"
                        class="w-full rounded-lg border-gray-300"
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

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- Priority --}}
                <div>

                    <label class="block mb-1 font-medium">
                        Priority
                    </label>

                    <select
                        wire:model="priority"
                        class="w-full rounded-lg border-gray-300"
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

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- Due Date --}}
                <div>

                    <label class="block mb-1 font-medium">
                        Due Date
                    </label>

                    <input
                        type="date"
                        wire:model="due_date"
                        class="w-full rounded-lg border-gray-300"
                    >

                    @error('due_date')

                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- Buttons --}}
                <div class="flex justify-end gap-3">

                    <button
                        type="button"
                        wire:click="$set('showEditForm', false)"
                        class="px-4 py-2 border rounded-lg"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 bg-black text-white rounded-lg"
                    >
                        Update Task
                    </button>

                </div>

            </form>

        </div>

    @endif


    {{-- Tasks --}}
    @if ($tasks->count())

        <div class="mb-4 text-sm text-gray-500">
            Showing {{ $tasks->count() }} task(s)
        </div>


        <div class="space-y-6">

            @foreach ($tasks as $task)

                <div
                    wire:key="project-task-{{ $task->id }}"
                    class="p-5 bg-white border rounded-xl shadow-sm"
                >

                    {{-- Task Header --}}
                    <div class="flex items-start justify-between">

                        <div>

                            <h3 class="text-lg font-semibold">
                                {{ $task->title }}
                            </h3>

                            @if ($task->description)

                                <p class="mt-2 text-sm text-gray-600">
                                    {{ $task->description }}
                                </p>

                            @endif

                        </div>

                        <span class="px-3 py-1 text-xs rounded-full bg-gray-100">

                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}

                        </span>

                    </div>


                    {{-- Task Details --}}
                    <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-500">

                        <span>
                            Priority:
                            {{ ucfirst($task->priority) }}
                        </span>

                        @if ($task->assignee)

                            <span>
                                Assigned to:
                                {{ $task->assignee->name }}
                            </span>

                        @else

                            <span>
                                Unassigned
                            </span>

                        @endif

                        @if ($task->due_date)

                            <span>
                                Due:
                                {{ $task->due_date->format('M d, Y') }}
                            </span>

                        @endif

                    </div>


                    {{-- Task Actions --}}
                    <div class="mt-5 flex gap-2">

                        <button
                            type="button"
                            wire:click="editTask({{ $task->id }})"
                            class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
                        >
                            Edit
                        </button>

                        <button
                            type="button"
                            wire:click="deleteTask({{ $task->id }})"
                            wire:confirm="Are you sure you want to delete this task?"
                            class="px-4 py-2 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50"
                        >
                            Delete
                        </button>

                    </div>


                    {{-- Comments --}}
                    <div class="mt-6 pt-5 border-t">

                        <div class="flex items-center justify-between">

                            <h4 class="font-semibold">

                                Comments

                                <span class="text-sm font-normal text-gray-500">
                                    ({{ $task->comments->count() }})
                                </span>

                            </h4>

                        </div>


                        {{-- Existing Comments --}}
                        @if ($task->comments->count())

                            <div class="mt-4 space-y-4">

                                @foreach ($task->comments as $comment)

                                    <div
                                        wire:key="task-{{ $task->id }}-comment-{{ $comment->id }}"
                                        class="p-4 bg-gray-50 rounded-lg"
                                    >

                                        {{-- Comment Header --}}
                                        <div class="flex items-start justify-between">

                                            <div>

                                                <p class="font-medium">
                                                    {{ $comment->user->name }}
                                                </p>

                                                <p class="text-xs text-gray-500">
                                                    {{ $comment->created_at->format('M d, Y \a\t H:i') }}
                                                </p>

                                            </div>


                                            {{-- Comment Actions --}}
                                            @if ($comment->user_id === auth()->id())

                                                <div class="flex gap-3">

                                                    <button
                                                        type="button"
                                                        wire:click="editComment({{ $comment->id }})"
                                                        class="text-sm text-gray-600 hover:text-black"
                                                    >
                                                        Edit
                                                    </button>

                                                    <button
                                                        type="button"
                                                        wire:click="deleteComment({{ $comment->id }})"
                                                        wire:confirm="Are you sure you want to delete this comment?"
                                                        class="text-sm text-red-600 hover:text-red-800"
                                                    >
                                                        Delete
                                                    </button>

                                                </div>

                                            @endif

                                        </div>


                                        {{-- Edit Comment --}}
                                        @if ($editingCommentId === $comment->id)

                                            <div class="mt-4">

                                                <textarea
                                                    wire:model="editingCommentText"
                                                    rows="4"
                                                    class="w-full rounded-lg border-gray-300"
                                                ></textarea>

                                                @error('editingCommentText')

                                                    <p class="mt-1 text-sm text-red-600">
                                                        {{ $message }}
                                                    </p>

                                                @enderror

                                                <div class="mt-3 flex justify-end gap-2">

                                                    <button
                                                        type="button"
                                                        wire:click="cancelCommentEdit"
                                                        class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
                                                    >
                                                        Cancel
                                                    </button>

                                                    <button
                                                        type="button"
                                                        wire:click="updateComment"
                                                        class="px-4 py-2 text-sm bg-black text-white rounded-lg hover:bg-gray-800"
                                                    >
                                                        Update Comment
                                                    </button>

                                                </div>

                                            </div>

                                        @else

                                            {{-- Comment Body --}}
                                            <p class="mt-3 text-sm text-gray-700 whitespace-pre-line">
                                                {{ $comment->body }}
                                            </p>

                                        @endif

                                    </div>

                                @endforeach

                            </div>

                        @else

                            <p class="mt-3 text-sm text-gray-500">
                                No comments yet.
                            </p>

                        @endif


                        {{-- Add Comment --}}
                        <div class="mt-5">

                            <label class="block mb-2 text-sm font-medium">
                                Add a comment
                            </label>

                            <textarea
                                wire:model="commentText.{{ $task->id }}"
                                rows="3"
                                maxlength="2000"
                                class="w-full rounded-lg border-gray-300"
                                placeholder="Write a comment..."
                            ></textarea>

                            @error("commentText.{$task->id}")

                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                            <div class="mt-3 flex items-center justify-between">

                                <span class="text-xs text-gray-500">
                                    Maximum 2000 characters
                                </span>

                                <button
                                    type="button"
                                    wire:click="addComment({{ $task->id }})"
                                    class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800"
                                >
                                    Add Comment
                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    @else

        <div class="p-8 text-center border rounded-xl">

            @if (
                $search !== '' ||
                $filterStatus !== '' ||
                $filterPriority !== '' ||
                $filterAssignee !== ''
            )

                <h2 class="text-lg font-semibold">
                    No matching tasks
                </h2>

                <p class="mt-2 text-gray-600">
                    No tasks match your current search or filters.
                </p>

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="mt-4 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800"
                >
                    Clear Filters
                </button>

            @else

                <h2 class="text-lg font-semibold">
                    No tasks yet
                </h2>

                <p class="mt-2 text-gray-600">
                    This project doesn't have any tasks yet.
                </p>

            @endif

        </div>

    @endif

</div>

