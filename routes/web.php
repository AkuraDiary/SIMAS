<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/force-login', function () {
//     $user = User::where('username', 'ayam')->first(); // Ensure this user exists
    
//     if (!$user) return "User not found";
    
//     Auth::login($user);
    
//     return redirect('/');
// });