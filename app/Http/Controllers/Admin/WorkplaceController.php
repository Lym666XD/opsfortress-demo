<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Audit\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWorkplaceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * M10 Slice 1 — Admin workplace management (list + create).
 *
 * Tenant scoping: every query against Workplace and Business runs through the
 * BelongsToTenant global scope, which auto-filters by the authenticated
 * user's tenant_id (resolved in SetTenantContext middleware). No explicit
 * where('tenant_id', ...) clauses needed here.
 *
 * Audit: workplace creation writes one ADMIN-anchored hash-chain event.
 */
final class WorkplaceController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Workplace::class);

        $workplaces = Workplace::query()
            ->orderBy('name')
            ->get(['id', 'business_id', 'name', 'code', 'suburb', 'state', 'active', 'created_at']);

        return Inertia::render('admin/workplaces/index', [
            'workplaces' => $workplaces,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Workplace::class);

        $businesses = Business::query()
            ->orderBy('legal_name')
            ->get(['id', 'legal_name', 'trading_name']);

        return Inertia::render('admin/workplaces/create', [
            'businesses' => $businesses,
            'defaultBusinessId' => $request->user()->business_id,
        ]);
    }

    public function store(StoreWorkplaceRequest $request): RedirectResponse
    {
        $workplace = Workplace::create($request->validated());

        $this->audit->record(
            subject: $workplace,
            anchor: AuditService::ANCHOR_ADMIN_CONFIG,
            eventName: 'workplace.created',
            payload: $workplace->only([
                'id', 'business_id', 'name', 'code', 'classification',
                'suburb', 'state', 'postcode', 'country',
                'latitude', 'longitude', 'geofence_radius_meters',
            ]),
            userId: $request->user()->id,
            businessId: $workplace->business_id,
        );

        return to_route('admin.workplaces.index');
    }
}
