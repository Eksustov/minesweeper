<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MinesweeperController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\GameController;
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

Route::get('rooms/json', [App\Http\Controllers\RoomController::class, 'json'])->name('rooms.json');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('rooms')->group(function () {
        Route::get('/', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::post('/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
        Route::post('/{room}/leave', [RoomController::class, 'leave'])->name('rooms.leave');
        Route::post('/{room}/kick', [RoomController::class, 'kick'])->name('rooms.kick');
        Route::post('/join-by-code', [RoomController::class, 'joinByCode'])->name('rooms.joinByCode');
    });
    
    Route::prefix('games')->group(function () {
        Route::get('/{room}', [GameController::class, 'game'])->name('games.show');
        Route::post('/{room}/start', [GameController::class, 'start'])->name('games.start');
        Route::post('/{room}/update', [GameController::class, 'update'])->name('games.update');
        Route::post('/{room}/restart', [GameController::class, 'restart'])->name('games.restart');
    });
});

require __DIR__.'/auth.php';
