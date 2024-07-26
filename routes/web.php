<?php

use App\Http\Controllers\GoogleDriveController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GoogleDriveController::class, 'listFilesInDriveFolder'])->name('listFilesInDriveFolder');
Route::get('/create-folder',[GoogleDriveController::class, 'createDriveFolder'])->name('createDriveFolder');
Route::get('/uploadfile', [GoogleDriveController::class,  'uploadFileToDriveFolder'])->name('uploadFileToDriveFolder');
Route::get('/readfile', [GoogleDriveController::class,  'readFileFromDrive'])->name('readFileFromDrive');
Route::get('/editfile', [GoogleDriveController::class,  'editFileInDrive'])->name('editFileInDrive');
