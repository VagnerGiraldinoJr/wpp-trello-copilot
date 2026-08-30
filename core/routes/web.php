<?php

use App\Livewire\Setup\Index as SetupIndex;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/setup');

Route::get('/setup', SetupIndex::class)->name('setup');
