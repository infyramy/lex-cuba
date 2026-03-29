<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\CaseSummary;
use App\Models\Category;
use App\Models\FreeLink;
use App\Models\MemberSubscription;
use App\Models\Note;
use App\Models\Package;
use App\Models\Question;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function summary(): JsonResponse
    {
        $now   = Carbon::now();
        $in30  = Carbon::now()->addDays(30);
        $start = Carbon::now()->startOfMonth();

        // ── Raw counts ────────────────────────────────────────────────────
        $totalMembers    = User::members()->count();
        $activeMembers   = User::members()->where('status', 'active')->count();
        $suspendedMembers = User::members()->where('status', 'suspended')->count();

        $totalNotes      = Note::count();
        $pubNotes        = Note::where('is_published', true)->count();
        $totalCases      = CaseSummary::count();
        $pubCases        = CaseSummary::where('is_published', true)->count();
        $totalQuestions  = Question::count();
        $pubQuestions    = Question::where('is_published', true)->count();
        $activeFreeLinks = FreeLink::where('is_active', true)->count();

        $allSubs     = MemberSubscription::count();
        $activeSubs  = MemberSubscription::where('expires_at', '>', $now)->count();
        $expiredSubs = $allSubs - $activeSubs;
        $expiringSoon = MemberSubscription::where('expires_at', '>', $now)
            ->where('expires_at', '<=', $in30)
            ->count();
        $membersWithSub = MemberSubscription::count(); // unique (user_id unique index)

        // ── Subscription conversion rate ─────────────────────────────────
        $conversionRate = $totalMembers > 0
            ? round(($membersWithSub / $totalMembers) * 100)
            : 0;

        // ── Active member rate ───────────────────────────────────────────
        $activationRate = $totalMembers > 0
            ? round(($activeMembers / $totalMembers) * 100)
            : 0;

        // ── Most popular package ─────────────────────────────────────────
        $popularPackage = MemberSubscription::select('package_name', DB::raw('count(*) as cnt'))
            ->groupBy('package_name')
            ->orderByDesc('cnt')
            ->first();

        // ── Content health (published %) ─────────────────────────────────
        $contentHealth = [
            ['label' => 'Notes',          'published' => $pubNotes,    'total' => $totalNotes,    'color' => '#6366f1'],
            ['label' => 'Case Summaries', 'published' => $pubCases,    'total' => $totalCases,    'color' => '#f59e0b'],
            ['label' => 'Questions',      'published' => $pubQuestions, 'total' => $totalQuestions, 'color' => '#14b8a6'],
        ];

        foreach ($contentHealth as &$item) {
            $item['pct'] = $item['total'] > 0 ? round(($item['published'] / $item['total']) * 100) : 0;
        }
        unset($item);

        // ── Member status breakdown ───────────────────────────────────────
        $memberStatus = [
            ['label' => 'Active',    'value' => $activeMembers,   'color' => '#10b981'],
            ['label' => 'Suspended', 'value' => $suspendedMembers, 'color' => '#f43f5e'],
        ];

        // ── Subscription pipeline ─────────────────────────────────────────
        $subPipeline = [
            ['label' => 'Active',        'value' => $activeSubs - $expiringSoon, 'color' => '#10b981'],
            ['label' => 'Expiring (30d)', 'value' => $expiringSoon,              'color' => '#f59e0b'],
            ['label' => 'Expired',        'value' => $expiredSubs,               'color' => '#94a3b8'],
        ];

        // ── Counts ───────────────────────────────────────────────────────
        $counts = [
            'admins'                => User::admins()->count(),
            'members'               => $totalMembers,
            'active_members'        => $activeMembers,
            'suspended_members'     => $suspendedMembers,
            'active_subscriptions'  => $activeSubs,
            'notes'                 => $totalNotes,
            'case_summaries'        => $totalCases,
            'questions'             => $totalQuestions,
            'free_links'            => $activeFreeLinks,
            'packages'              => Package::where('is_active', true)->count(),
        ];

        // ── Recent members ────────────────────────────────────────────────
        $recentMembers = User::members()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'name', 'email', 'status', 'created_at']);

        return $this->sendOk([
            'counts'  => $counts,
            'metrics' => [
                'conversion_rate'   => $conversionRate,
                'activation_rate'   => $activationRate,
                'expiring_soon'     => $expiringSoon,
                'popular_package'   => $popularPackage?->package_name,
                'popular_pkg_count' => $popularPackage?->cnt ?? 0,
            ],
            'charts'  => [
                'member_status'   => $memberStatus,
                'content_health'  => $contentHealth,
                'sub_pipeline'    => $subPipeline,
            ],
            'recent'  => [
                'members' => $recentMembers,
            ],
        ]);
    }
}
