<?php

use App\Http\Controllers\PauseController;
use Illuminate\Support\Facades\Route;

Route::get('/{seconds?}', [PauseController::class, 'pausePiHoles']);
