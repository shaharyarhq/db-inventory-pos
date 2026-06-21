<?php

use App\Models\Purchase\Purchase;
use App\Models\Sale\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;


Route::get('/optimize', function () {
    Artisan::call('optimize:clear');
    Artisan::call('optimize');

    return back();
})->name('optimize');

Route::get('/optimize/clear', function () {
    $output = Artisan::call('optimize:clear');

    return back();
})->name('optimize:clear');

Route::get('/terminal/{any?}', function () {
    return file_get_contents(public_path('terminal/index.html'));
})->where('any', '.*');

Route::get('/print-pdf/{model}/{id}', function (string $modelClass, int $id, Request $request) {

    abort_if(!class_exists($modelClass) || !method_exists($modelClass, 'isPrintable') || !$modelClass::isPrintable(), 403);

    $record = $modelClass::findOrFail($id);

    $html = view($request->query('view'), [
        'record' => $record,
        'params' => $request->query('params'),
    ])->render();

    return response(Pdf::loadHTML($html)
        ->setOption('defaultFont', 'DejaVu Sans')
        ->setPaper('A4', 'portrait')
        ->output())
        ->header('Content-Type', 'application/pdf');
})->middleware('auth')->name('print.pdf');
