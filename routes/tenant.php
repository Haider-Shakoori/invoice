<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn()=>response()->json([
    'product'=>'Invoice Drafts SaaS',
    'scope'=>'tenant',
    'tenant'=>tenant('id'),
]))->name('tenant.home');
