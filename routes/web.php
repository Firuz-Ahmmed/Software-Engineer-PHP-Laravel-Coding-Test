<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;

Route::post('/users',[UserController::class,'create']);
Route::post('/login',[UserController::class,'login']);
Route::get('/transactions-and-balance', [TransactionController::class, 'showTransactionsAndBalance']);
Route::get('/deposit',[TransactionController::class,'allDeposits']);
Route::post('/deposit',[TransactionController::class,'deposits']);
Route::get('/withdrawal',[TransactionController::class,'allWithdrawals']);
Route::post('/withdrawal',[TransactionController::class,'withdrawals']);

