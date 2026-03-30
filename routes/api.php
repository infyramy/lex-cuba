<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\DevConsoleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseSummaryController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FreeLinkController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MemberSubscriptionController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TopicLinkController;
use App\Http\Controllers\Api\QuestionPaperController;
use App\Http\Controllers\Api\StatuteController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ── Auth routes ──────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::post('/avatar', [AuthController::class, 'uploadAvatar'])->middleware('throttle:uploads');
        Route::delete('/avatar', [AuthController::class, 'removeAvatar']);
    });
});

// ── Settings GET is public (used by SPA before auth) ─────────────────────────
Route::get('/settings', [SettingController::class, 'index']);

// ── Protected admin routes ───────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // ── Admin Users ──────────────────────────────────────────────────────────
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
    Route::put('/users/{user}/status', [UserController::class, 'updateStatus'])->middleware('permission:users.edit');

    // ── Roles & Permissions ──────────────────────────────────────────────────
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('permission:roles.view');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');
    Route::get('/permissions', function () {
        return response()->json(['data' => \App\Enums\Permission::all()]);
    })->middleware('permission:roles.view');

    // ── Members (mobile app users) ───────────────────────────────────────────
    Route::get('/members', [MemberController::class, 'index'])->middleware('permission:members.view');
    Route::post('/members', [MemberController::class, 'store'])->middleware('permission:members.create');
    Route::get('/members/{member}', [MemberController::class, 'show'])->middleware('permission:members.view');
    Route::put('/members/{member}', [MemberController::class, 'update'])->middleware('permission:members.edit');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->middleware('permission:members.delete');
    Route::put('/members/{member}/status', [MemberController::class, 'updateStatus'])->middleware('permission:members.edit');

    // Member subscriptions
    Route::get('/members/{member}/subscription', [MemberSubscriptionController::class, 'show'])->middleware('permission:members.view');
    Route::post('/members/{member}/subscription', [MemberSubscriptionController::class, 'store'])->middleware('permission:members.edit');
    Route::delete('/members/{member}/subscription', [MemberSubscriptionController::class, 'destroy'])->middleware('permission:members.edit');

    // ── Subscription Packages ────────────────────────────────────────────────
    Route::get('/packages', [PackageController::class, 'index'])->middleware('permission:packages.view');
    Route::post('/packages', [PackageController::class, 'store'])->middleware('permission:packages.create');
    Route::get('/packages/{package}', [PackageController::class, 'show'])->middleware('permission:packages.view');
    Route::put('/packages/{package}', [PackageController::class, 'update'])->middleware('permission:packages.edit');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->middleware('permission:packages.delete');

    // ── Content Management — Topics (Categories) ─────────────────────────────
    Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:categories.view');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.edit');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete');

    // ── Content Management — Topic Links ─────────────────────────────────────
    Route::get('/topic-links', [TopicLinkController::class, 'index'])->middleware('permission:topic_links.view');
    Route::post('/topic-links', [TopicLinkController::class, 'store'])->middleware('permission:topic_links.create');
    Route::get('/topic-links/{topic_link}', [TopicLinkController::class, 'show'])->middleware('permission:topic_links.view');
    Route::put('/topic-links/{topic_link}', [TopicLinkController::class, 'update'])->middleware('permission:topic_links.edit');
    Route::delete('/topic-links/{topic_link}', [TopicLinkController::class, 'destroy'])->middleware('permission:topic_links.delete');

    // ── Content Management — Notes ───────────────────────────────────────────
    Route::get('/notes', [NoteController::class, 'index'])->middleware('permission:notes.view');
    Route::post('/notes', [NoteController::class, 'store'])->middleware('permission:notes.create');
    Route::get('/notes/{note}', [NoteController::class, 'show'])->middleware('permission:notes.view');
    Route::put('/notes/{note}', [NoteController::class, 'update'])->middleware('permission:notes.edit');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->middleware('permission:notes.delete');
    Route::post('/notes/{note}/upload', [NoteController::class, 'uploadFile'])->middleware(['permission:notes.create', 'throttle:uploads']);

    // ── Content Management — Case Summaries ──────────────────────────────────
    Route::get('/case-summaries', [CaseSummaryController::class, 'index'])->middleware('permission:case_summaries.view');
    Route::post('/case-summaries', [CaseSummaryController::class, 'store'])->middleware('permission:case_summaries.create');
    Route::get('/case-summaries/{case_summary}', [CaseSummaryController::class, 'show'])->middleware('permission:case_summaries.view');
    Route::put('/case-summaries/{case_summary}', [CaseSummaryController::class, 'update'])->middleware('permission:case_summaries.edit');
    Route::delete('/case-summaries/{case_summary}', [CaseSummaryController::class, 'destroy'])->middleware('permission:case_summaries.delete');

    // ── Content Management — Question Bank ───────────────────────────────────
    Route::get('/questions', [QuestionController::class, 'index'])->middleware('permission:questions.view');
    Route::post('/questions', [QuestionController::class, 'store'])->middleware('permission:questions.create');
    Route::get('/questions/{question}', [QuestionController::class, 'show'])->middleware('permission:questions.view');
    Route::put('/questions/{question}', [QuestionController::class, 'update'])->middleware('permission:questions.edit');
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->middleware('permission:questions.delete');

    // ── Content Management — Question Papers ─────────────────────────────────
    Route::get('/question-papers', [QuestionPaperController::class, 'index'])->middleware('permission:questions.view');
    Route::post('/question-papers', [QuestionPaperController::class, 'store'])->middleware('permission:questions.create');
    Route::get('/question-papers/{question_paper}', [QuestionPaperController::class, 'show'])->middleware('permission:questions.view');
    Route::put('/question-papers/{question_paper}', [QuestionPaperController::class, 'update'])->middleware('permission:questions.edit');
    Route::delete('/question-papers/{question_paper}', [QuestionPaperController::class, 'destroy'])->middleware('permission:questions.delete');
    Route::post('/question-papers/{question_paper}/upload', [QuestionPaperController::class, 'uploadFile'])->middleware(['permission:questions.create', 'throttle:uploads']);

    // ── Content Management — Statutes ────────────────────────────────────────
    Route::get('/statutes', [StatuteController::class, 'index'])->middleware('permission:statutes.view');
    Route::post('/statutes', [StatuteController::class, 'store'])->middleware('permission:statutes.create');
    Route::get('/statutes/{statute}', [StatuteController::class, 'show'])->middleware('permission:statutes.view');
    Route::put('/statutes/{statute}', [StatuteController::class, 'update'])->middleware('permission:statutes.edit');
    Route::delete('/statutes/{statute}', [StatuteController::class, 'destroy'])->middleware('permission:statutes.delete');
    Route::post('/statutes/{statute}/upload', [StatuteController::class, 'uploadFile'])->middleware(['permission:statutes.create', 'throttle:uploads']);

    // ── App Configuration — Free Links ───────────────────────────────────────
    Route::get('/free-links', [FreeLinkController::class, 'index'])->middleware('permission:free_links.view');
    Route::post('/free-links', [FreeLinkController::class, 'store'])->middleware('permission:free_links.create');
    Route::get('/free-links/{free_link}', [FreeLinkController::class, 'show'])->middleware('permission:free_links.view');
    Route::put('/free-links/{free_link}', [FreeLinkController::class, 'update'])->middleware('permission:free_links.edit');
    Route::delete('/free-links/{free_link}', [FreeLinkController::class, 'destroy'])->middleware('permission:free_links.delete');

    // ── Media ────────────────────────────────────────────────────────────────
    Route::get('/media', [MediaController::class, 'index'])->middleware('permission:media.view');
    Route::post('/media/upload', [MediaController::class, 'upload'])->middleware(['permission:media.upload', 'throttle:uploads']);
    Route::put('/media/{media}', [MediaController::class, 'update'])->middleware('permission:media.upload');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->middleware('permission:media.delete');

    // ── Settings ─────────────────────────────────────────────────────────────
    Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:settings.edit');

    // ── Dashboard ────────────────────────────────────────────────────────────
    Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->middleware('permission:settings.view');

    // ── Audit Logs ───────────────────────────────────────────────────────────
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.read');
});

// ── Dev Console (remove before final production) ─────────────────────────────
Route::prefix('dev')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('info',                 [DevConsoleController::class, 'info']);
    Route::get('tables',               [DevConsoleController::class, 'tables']);
    Route::get('tables/{table}',       [DevConsoleController::class, 'tableRows']);
    Route::post('tables/{table}',      [DevConsoleController::class, 'createRow']);
    Route::put('tables/{table}/{id}',  [DevConsoleController::class, 'updateRow']);
    Route::delete('tables/{table}/{id}', [DevConsoleController::class, 'deleteRow']);
});

// ── Mobile API (token-based auth) ────────────────────────────────────────────
Route::prefix('mobile')->group(function () {
    // Public (no auth required)
    Route::post('/register', [MobileAuthController::class, 'register'])->middleware('throttle:login');
    Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:login');

    // Protected (bearer token)
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::put('/me', [MobileAuthController::class, 'updateProfile']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);
        Route::post('/refresh-token', [MobileAuthController::class, 'refresh']);
        Route::post('/revoke-all', [MobileAuthController::class, 'revokeAll']);

        // Read-only content endpoints for mobile consumption
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::get('/notes', [NoteController::class, 'index']);
        Route::get('/notes/{note}', [NoteController::class, 'show']);
        Route::get('/case-summaries', [CaseSummaryController::class, 'index']);
        Route::get('/case-summaries/{case_summary}', [CaseSummaryController::class, 'show']);
        Route::get('/questions', [QuestionController::class, 'index']);
        Route::get('/questions/{question}', [QuestionController::class, 'show']);
        Route::get('/question-papers', [QuestionPaperController::class, 'index']);
        Route::get('/question-papers/{question_paper}', [QuestionPaperController::class, 'show']);
        Route::get('/statutes', [StatuteController::class, 'index']);
        Route::get('/statutes/{statute}', [StatuteController::class, 'show']);
        Route::get('/free-links', [FreeLinkController::class, 'index']);
        Route::get('/packages', [PackageController::class, 'index']);
        Route::get('/packages/{package}', [PackageController::class, 'show']);
    });
});
