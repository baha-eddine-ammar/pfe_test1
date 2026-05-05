<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentHeadInviteRequest;
use App\Models\DepartmentHeadInvite;
use App\Services\DepartmentHeadInviteService;
use Illuminate\Http\RedirectResponse;

class DepartmentHeadInviteController extends Controller
{
    public function store(StoreDepartmentHeadInviteRequest $request, DepartmentHeadInviteService $departmentHeadInviteService): RedirectResponse
    {
        $departmentHeadInviteService->createInvite(
            $request->user(),
            $request->validated('invited_email'),
        );

        return back()->with('status', 'Department Head invite sent successfully.');
    }

    public function revoke(DepartmentHeadInvite $departmentHeadInvite, DepartmentHeadInviteService $departmentHeadInviteService): RedirectResponse
    {
        if (! $departmentHeadInvite->isUsed() && ! $departmentHeadInvite->isRevoked()) {
            $departmentHeadInviteService->revokeInvite($departmentHeadInvite, request()->user());
        }

        return back()->with('status', 'Department Head invite updated successfully.');
    }
}
