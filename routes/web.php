<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MinesweeperController;
use App\Http\Controllers\RoomController;
use App\Models\Room;

Route::get('/', function () {
    $rooms = \App\Models\Room::all();
    return view('welcome', compact('rooms'));
})->name('welcome');

Route::get('/aboutus', function () {
    return view('about');
})->name('about');

Route::get('/contacts', function () {
    return view('contacts');
})->name('contacts');

Route::get('/minesweeper', [MinesweeperController::class, 'index'])->name('minesweeper');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/rooms/json', [RoomController::class, 'roomsJson']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');

    Route::prefix('rooms/{room}')->group(function () {
        Route::get('/', [RoomsController::class, 'show'])->name('rooms.show');
        Route::post('/join', [RoomController::class, 'join'])->name('rooms.join');
        Route::post('/kick', [RoomController::class, 'kick'])->name('rooms.kick');
        Route::post('/leave', [RoomController::class, 'leave'])->name('rooms.leave');

        Route::get('/game', [RoomController::class, 'game'])->name('rooms.game');
        Route::post('/restart', [GameController::class, 'restart'])->name('games.restart');
        Route::post('/update', [GameController::class, 'update'])->name('games.update');
    });

    Route::post('/games/update', [RoomController::class, 'update'])->name('games.update');
});

require __DIR__.'/auth.php';
