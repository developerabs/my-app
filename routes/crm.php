<?php

use App\Http\Controllers\CRM\LeadController;
use App\Http\Controllers\CRM\LeadImportController;
use App\Http\Controllers\CRM\LeadSourceController;
use App\Http\Controllers\CRM\LeadSubjectController;
use App\Http\Controllers\CRM\MeetingController;
use App\Http\Controllers\CRM\StatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->group(function () {
    Route::controller(LeadSubjectController::class)->prefix('lead-subjects')->middleware('check_active:leads_active')->group(function () {
        Route::get('/', 'index')->middleware('permission:crm_leads_manage_subject')->name('lead-subjects.index');
        Route::post('/', 'store')->middleware('permission:crm_leads_manage_subject')->name('lead-subjects.store');
        Route::get('/edit/{leadSubject}', 'edit')->middleware('permission:crm_leads_manage_subject')->name('lead-subjects.edit');
        Route::patch('/update/{leadSubject}', 'update')->middleware('permission:crm_leads_manage_subject')->name('lead-subjects.update');
        Route::delete('/delete/{leadSubject}', 'destroy')->middleware('permission:crm_leads_manage_subject')->name('lead-subjects.destroy');
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:crm_leads_manage_subject')->name('lead-subjects.bulk-delete');
    });

    Route::controller(LeadSourceController::class)->prefix('lead-sources')->middleware('check_active:leads_active')->group(function () {
        Route::get('/', 'index')->middleware('permission:crm_leads_manage_source')->name('lead-sources.index');
        Route::post('/', 'store')->middleware('permission:crm_leads_manage_source')->name('lead-sources.store');
        Route::get('/edit/{leadSource}', 'edit')->middleware('permission:crm_leads_manage_source')->name('lead-sources.edit');
        Route::patch('/update/{leadSource}', 'update')->middleware('permission:crm_leads_manage_source')->name('lead-sources.update');
        Route::delete('/delete/{leadSource}', 'destroy')->middleware('permission:crm_leads_manage_source')->name('lead-sources.destroy');
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:crm_leads_manage_source')->name('lead-sources.bulk-delete');
    });

    Route::controller(StatusController::class)->prefix('statuses')->middleware('check_active:leads_active')->group(function () {
        Route::get('/', 'index')->middleware('permission:crm_leads_manage_status')->name('statuses.index');
        Route::post('/', 'store')->middleware('permission:crm_leads_manage_status')->name('statuses.store');
        Route::get('/edit/{status}', 'edit')->middleware('permission:crm_leads_manage_status')->name('statuses.edit');
        Route::patch('/update/{status}', 'update')->middleware('permission:crm_leads_manage_status')->name('statuses.update');
        Route::delete('/delete/{status}', 'destroy')->middleware('permission:crm_leads_manage_status')->name('statuses.destroy');
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:crm_leads_manage_status')->name('statuses.bulk-delete');
        Route::get('/get-statuses-by-category-and-type', 'getStatusesByCategoryAndType')->name('statuses.by-category-and-type');
        // Route::get('/get-statuses-by-type', 'getStatusesByType')->name('statuses.by-type');
    });

    Route::controller(MeetingController::class)->prefix('meetings')->middleware('check_active:leads_active')->group(function () {
        Route::get('/', 'index')->middleware('permission:crm_leads_notes_view')->name('meetings.index');
        Route::get('/events', 'events')->middleware('permission:crm_leads_notes_view')->name('meetings.events');
        Route::get('/statuses', 'statuses')->middleware('permission:crm_leads_notes_create')->name('meetings.statuses');
        Route::get('/{meeting}', 'show')->middleware('permission:crm_leads_notes_view')->name('meetings.show');
        Route::patch('/{meeting}', 'update')->middleware('permission:crm_leads_notes_update')->name('meetings.update');
        Route::post('/{meeting}/complete', 'complete')->middleware('permission:crm_leads_notes_update')->name('meetings.complete');
    });

    Route::controller(LeadController::class)->prefix('leads')->middleware('check_active:leads_active')->group(function () {
        Route::get('/', 'index')->middleware('permission:crm_leads_view')->name('leads.index');
        Route::post('/', 'store')->middleware('permission:crm_leads_create')->name('leads.store');
        Route::get('/edit/{lead}', 'edit')->middleware('permission:crm_leads_update')->name('leads.edit');
        Route::patch('/update/{lead}', 'update')->middleware('permission:crm_leads_update')->name('leads.update');
        Route::get('/show/{lead}', 'show')->middleware('permission:crm_leads_notes_view')->name('leads.show');
        Route::post('/{lead}/notes', 'addNote')->middleware('permission:crm_leads_notes_create')->name('leads.add-note');
        Route::get('/{lead}/convert', 'convertToCustomer')->middleware('permission:crm_leads_update')->name('leads.convert');
        Route::post('/{lead}/convert-deal', 'convertToDeal')->middleware('permission:crm_leads_update')->name('leads.convert-deal');
        Route::post('/{lead}/fail', 'markFailed')->middleware('permission:crm_leads_update')->name('leads.fail');
        Route::post('/{lead}/unfail', 'removeFromFailed')->middleware('permission:crm_leads_update')->name('leads.unfail');
        Route::get('/{lead}/history', 'history')->middleware('permission:crm_leads_view')->name('leads.history');
        Route::delete('/delete/{lead}', 'destroy')->middleware('permission:crm_leads_delete')->name('leads.destroy');
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:crm_leads_delete')->name('leads.bulk-delete');
    });
    Route::get('/today-follow-up', [LeadController::class,'todaysFollowUp'])->middleware('permission:crm_leads_view')->name('leads.today-follow-up');
    Route::get('/deals', [LeadController::class,'deals'])->middleware('permission:crm_deals_view')->name('deals.index');

    // Lead Import Routes
    Route::post('/import', [LeadImportController::class, 'import'])->middleware('permission:crm_leads_import')->name('leads.import');
});