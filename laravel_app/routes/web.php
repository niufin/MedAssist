<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\HospitalDashboardController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    $findLatestFile = function (string $directory, array $extensions): ?array {
        if (!File::isDirectory($directory)) {
            return null;
        }

        $files = collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file) => in_array(strtolower($file->getExtension()), $extensions, true))
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $file = $files->first();

        return [
            'filename' => $file->getFilename(),
            'relative_path' => trim(str_replace(public_path(), '', $file->getPathname()), "\\/"),
            'size_bytes' => $file->getSize(),
            'updated_at' => Carbon::createFromTimestamp($file->getMTime()),
        ];
    };

    $windowsDownload = $findLatestFile(public_path('downloads/windows'), ['msi']);
    $androidDownload = $findLatestFile(public_path('downloads/android'), ['apk']);

    return view('welcome', [
        'windowsDownload' => $windowsDownload,
        'androidDownload' => $androidDownload,
    ]);
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::get('/contact-us', function () {
    return view('contact');
})->name('contact');

Route::post('/contact-us', [\App\Http\Controllers\ContactController::class, 'send'])->name('contact.send');

Route::get('/debug-php-config', function () {
    return [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'loaded_php_ini' => php_ini_loaded_file(),
        'scanned_files' => php_ini_scanned_files(),
    ];
});

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard & Search
    Route::get('/dashboard', [DoctorController::class, 'index'])->name('dashboard');
    Route::get('/hospital/dashboard', [HospitalDashboardController::class, 'index'])
        ->middleware('can:isHospitalAdmin')
        ->name('hospital.dashboard');
    
    // Patient Management
    Route::get('/doctor/patients', [DoctorController::class, 'patients'])->name('doctor.patients.index');
    Route::get('/doctor/patients/{id}', [DoctorController::class, 'showPatient'])->name('doctor.patients.show');
    Route::post('/doctor/patients/{id}/new-consultation', [DoctorController::class, 'newConsultationForPatient'])->name('doctor.patients.new_consultation');
    
    Route::get('/new-patient', [DoctorController::class, 'newPatient'])->name('new.patient');
    Route::delete('/consultation/{id}', [DoctorController::class, 'destroy'])->name('consultation.destroy');
    Route::delete('/consultations/delete-all', [DoctorController::class, 'destroyAll'])->name('consultations.destroyAll');
    Route::post('/consultations/{id}/attach-patient', [DoctorController::class, 'attachPatient'])->name('consultations.attachPatient');
    Route::get('/consultations/backfill-patients', [DoctorController::class, 'backfillPatients'])->name('consultations.backfill');
    
    // Chat & Upload
    Route::post('/chat', [DoctorController::class, 'chat'])->name('chat.send');
    Route::post('/chat/update-prescription', [DoctorController::class, 'updatePrescriptionFromChat'])->name('chat.update_prescription');
    Route::post('/intake', [DoctorController::class, 'submitIntake'])->name('intake.submit');
    Route::post('/upload-report', [DoctorController::class, 'uploadReport'])->name('upload.report');
    Route::post('/analyze-reports', [DoctorController::class, 'analyzeReports'])->name('analyze.reports');
    Route::post('/update-prescription-reports', [DoctorController::class, 'updatePrescriptionFromReports'])->name('update.prescription.reports');
    Route::get('/lab/report/view/{id}', [\App\Http\Controllers\LabAssistantController::class, 'viewReport'])->name('lab.report.view'); // <--- SHARED VIEW ROUTE // <--- UPLOAD ROUTE

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.markRead');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/prescription/edit/{id}', [DoctorController::class, 'editPrescription'])->name('prescription.edit');
    Route::post('/prescription/update/{id}', [DoctorController::class, 'updatePrescription'])->name('prescription.update');
    Route::get('/prescription/{id}', [DoctorController::class, 'generatePrescription'])->name('prescription.generate');
    Route::get('/prescription/preview/{id}', [DoctorController::class, 'previewPrescription'])->name('prescription.preview');
    Route::get('/prescription/preview/raw/{id}', [DoctorController::class, 'previewPrescriptionRaw'])->name('prescription.preview.raw');
    Route::get('/prescription/download/{id}', [DoctorController::class, 'downloadPrescription'])->name('prescription.download');

    Route::get('/health', [HealthController::class, 'status'])->name('health.status');

    Route::prefix('api')->group(function () {
        Route::get('/medicines', [\App\Http\Controllers\Api\MedicineApiController::class, 'index']);
        Route::get('/medicines/{medicine}', [\App\Http\Controllers\Api\MedicineApiController::class, 'show']);
        Route::get('/ingredients', [\App\Http\Controllers\Api\IngredientApiController::class, 'index']);
    });

    // Admin Routes
    Route::middleware('can:isAdmin')->group(function () {
        Route::resource('admin/users', \App\Http\Controllers\AdminUserController::class)->names('admin.users');
        Route::post('admin/users/{user}/approve', [\App\Http\Controllers\AdminUserController::class, 'approve'])->name('admin.users.approve');
        
        Route::middleware('can:isPlatformAdmin')->prefix('admin')->name('admin.')->group(function () {
            Route::resource('hospitals', \App\Http\Controllers\Admin\HospitalController::class)
                ->parameters(['hospitals' => 'hospital'])
                ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

            Route::resource('pharmacies', \App\Http\Controllers\Admin\PharmacyStoreController::class)
                ->parameters(['pharmacies' => 'pharmacy'])
                ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        });

        // AI System Control (Super Admin Only)
        Route::middleware('can:isSuperAdmin')->group(function () {
            Route::post('admin/system/reload-ai', [\App\Http\Controllers\AdminSystemController::class, 'reloadAiMemory'])->name('admin.system.reload_ai');
            Route::post('admin/system/restart-ai', [\App\Http\Controllers\AdminSystemController::class, 'restartAiService'])->name('admin.system.restart_ai');
            Route::get('admin/scraper', [\App\Http\Controllers\AdminScraperController::class, 'index'])->name('admin.scraper.index');
            Route::post('admin/scraper/run', [\App\Http\Controllers\AdminScraperController::class, 'run'])->name('admin.scraper.run');
        });
    });

    // Pharmacist Routes
    Route::middleware('can:isPharmacist')->group(function () {
        Route::get('/pharmacist/dashboard', [\App\Http\Controllers\PharmacistController::class, 'index'])->name('pharmacist.dashboard');
        Route::post('/pharmacist/fulfill', [\App\Http\Controllers\PharmacistController::class, 'fulfill'])->name('pharmacist.fulfill');
    });

    // Pharmacy App Routes
    Route::middleware('can:isPharmacyStaff')->prefix('pharmacy')->name('pharmacy.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('pharmacy.inventory.index');
        })->name('home');

        Route::post('/context/hospital', [\App\Http\Controllers\Pharmacy\ContextController::class, 'setActiveHospital'])->name('context.hospital.set');

        Route::get('/inventory', [\App\Http\Controllers\Pharmacy\InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/{medicine}', [\App\Http\Controllers\Pharmacy\InventoryController::class, 'show'])->name('inventory.show');

        Route::get('/dispense', [\App\Http\Controllers\Pharmacy\DispenseController::class, 'index'])->name('dispense.index');
        Route::get('/dispense/{consultation}', [\App\Http\Controllers\Pharmacy\DispenseController::class, 'show'])->name('dispense.show');
        Route::post('/dispense/{consultation}/order', [\App\Http\Controllers\Pharmacy\DispenseController::class, 'createOrder'])->name('dispense.order.create');
        Route::post('/dispense/item/{item}/dispense', [\App\Http\Controllers\Pharmacy\DispenseController::class, 'dispenseItem'])->name('dispense.item.dispense');
        Route::post('/dispense/item/{item}/not-given', [\App\Http\Controllers\Pharmacy\DispenseController::class, 'markNotGiven'])->name('dispense.item.not_given');
        Route::post('/dispense/order/{order}/finalize', [\App\Http\Controllers\Pharmacy\DispenseController::class, 'finalize'])->name('dispense.order.finalize');
        Route::get('/api/stock-batches', [\App\Http\Controllers\Pharmacy\DispenseController::class, 'stockBatches'])->name('api.stock_batches');

        Route::get('/sales/invoices', [\App\Http\Controllers\Pharmacy\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/sales/invoices/{invoice}', [\App\Http\Controllers\Pharmacy\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/sales/invoices/{invoice}/print', [\App\Http\Controllers\Pharmacy\InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('/sales/invoices/{invoice}/pdf', [\App\Http\Controllers\Pharmacy\InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::post('/sales/invoices/{invoice}/payment', [\App\Http\Controllers\Pharmacy\InvoiceController::class, 'addPayment'])->name('invoices.payment');
        Route::post('/sales/invoices/{invoice}/return', [\App\Http\Controllers\Pharmacy\InvoiceController::class, 'processReturn'])->name('invoices.return');

        Route::get('/purchases/suppliers', [\App\Http\Controllers\Pharmacy\SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/purchases/suppliers/create', [\App\Http\Controllers\Pharmacy\SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/purchases/suppliers', [\App\Http\Controllers\Pharmacy\SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/purchases/suppliers/{supplier}/edit', [\App\Http\Controllers\Pharmacy\SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/purchases/suppliers/{supplier}', [\App\Http\Controllers\Pharmacy\SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/purchases/suppliers/{supplier}', [\App\Http\Controllers\Pharmacy\SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('/purchases/orders', [\App\Http\Controllers\Pharmacy\PurchaseOrderController::class, 'index'])->name('purchases.orders.index');
        Route::get('/purchases/orders/create', [\App\Http\Controllers\Pharmacy\PurchaseOrderController::class, 'create'])->name('purchases.orders.create');
        Route::post('/purchases/orders', [\App\Http\Controllers\Pharmacy\PurchaseOrderController::class, 'store'])->name('purchases.orders.store');
        Route::get('/purchases/orders/{order}', [\App\Http\Controllers\Pharmacy\PurchaseOrderController::class, 'show'])->name('purchases.orders.show');
        Route::post('/purchases/orders/{order}/items', [\App\Http\Controllers\Pharmacy\PurchaseOrderController::class, 'addItem'])->name('purchases.orders.items.add');
        Route::post('/purchases/orders/{order}/submit', [\App\Http\Controllers\Pharmacy\PurchaseOrderController::class, 'submit'])->name('purchases.orders.submit');
        Route::get('/purchases/orders/{order}/receive', [\App\Http\Controllers\Pharmacy\PurchaseOrderController::class, 'receiveForm'])->name('purchases.orders.receive.form');
        Route::post('/purchases/orders/{order}/receive', [\App\Http\Controllers\Pharmacy\PurchaseOrderController::class, 'receive'])->name('purchases.orders.receive');

        Route::get('/reports/stock', [\App\Http\Controllers\Pharmacy\ReportsController::class, 'stock'])->name('reports.stock');
        Route::get('/reports/near-expiry', [\App\Http\Controllers\Pharmacy\ReportsController::class, 'nearExpiry'])->name('reports.near_expiry');
        Route::get('/reports/sales', [\App\Http\Controllers\Pharmacy\ReportsController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/movements', [\App\Http\Controllers\Pharmacy\ReportsController::class, 'movements'])->name('reports.movements');
        Route::get('/reports/audit', [\App\Http\Controllers\Pharmacy\ReportsController::class, 'audit'])->name('reports.audit');

        Route::middleware('can:isAdmin')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Pharmacy\SettingsController::class, 'edit'])->name('settings.edit');
            Route::post('/settings', [\App\Http\Controllers\Pharmacy\SettingsController::class, 'update'])->name('settings.update');
        });

        Route::middleware('can:isSuperAdmin')->group(function () {
            Route::post('/medicines/clear', [\App\Http\Controllers\Pharmacy\MedicineController::class, 'clearAll'])->name('medicines.clear');
        });

        Route::get('/stock/in', [\App\Http\Controllers\Pharmacy\StockController::class, 'createIn'])->name('stock.in');
        Route::post('/stock/in', [\App\Http\Controllers\Pharmacy\StockController::class, 'storeIn'])->name('stock.in.store');
        Route::get('/stock/adjust/{batch}', [\App\Http\Controllers\Pharmacy\StockController::class, 'editAdjust'])->name('stock.adjust');
        Route::post('/stock/adjust/{batch}', [\App\Http\Controllers\Pharmacy\StockController::class, 'updateAdjust'])->name('stock.adjust.update');

        Route::get('/medicines', [\App\Http\Controllers\Pharmacy\MedicineController::class, 'index'])->name('medicines.index');

        Route::middleware('can:isAdmin')->group(function () {
            Route::get('/medicines/create', [\App\Http\Controllers\Pharmacy\MedicineController::class, 'create'])->name('medicines.create');
            Route::post('/medicines', [\App\Http\Controllers\Pharmacy\MedicineController::class, 'store'])->name('medicines.store');
            Route::get('/medicines/{medicine}/edit', [\App\Http\Controllers\Pharmacy\MedicineController::class, 'edit'])->name('medicines.edit');
            Route::put('/medicines/{medicine}', [\App\Http\Controllers\Pharmacy\MedicineController::class, 'update'])->name('medicines.update');
            Route::delete('/medicines/{medicine}', [\App\Http\Controllers\Pharmacy\MedicineController::class, 'destroy'])->name('medicines.destroy');
        });
    });

    // Lab Assistant Routes
    Route::middleware('can:isLabAccess')->group(function () {
        Route::get('/lab/dashboard', [\App\Http\Controllers\LabAssistantController::class, 'index'])->name('lab.dashboard');
        Route::post('/lab/upload', [\App\Http\Controllers\LabAssistantController::class, 'upload'])->name('lab.upload');
        Route::delete('/lab/report/{id}', [\App\Http\Controllers\LabAssistantController::class, 'destroy'])->name('lab.report.destroy');
    });

    // Patient Routes
    Route::get('/patient/dashboard', [\App\Http\Controllers\PatientController::class, 'index'])->name('patient.dashboard');
    Route::post('/patient/connect-visits', [\App\Http\Controllers\PatientController::class, 'connectVisits'])->name('patient.connect_visits');
});

require __DIR__.'/auth.php';
