<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');

        Route::livewire('projects', 'projects.⚡index')
            ->name('projects.index');
         Route::livewire('tasks', 'tasks.⚡index')
            ->name('tasks.index');
        Route::livewire('projects/{project}/tasks', 'tasks.⚡project')
            ->name('projects.tasks');
    });

require __DIR__.'/settings.php';
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
