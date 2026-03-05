<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{group}', function ($user, $group) {
    $user->loadMissing(['userType', 'status', 'employeeDetails']);

    $typeName   = $user->userType->type_name ?? '';
    $statusName = $user->status->status_name ?? '';
    $isSuperAdmin     = $typeName === 'super_admin';
    $isActiveEmployee = $typeName === 'employee' && $statusName === 'active';
    $isOngoingIntern  = $typeName === 'intern'   && $statusName === 'ongoing';
    $isLeader         = $user->employeeDetails?->hierarchy === 'Leader';

    if ($group === 'core') {
        return $isSuperAdmin || ($isActiveEmployee && $isLeader);
    }
    if ($group === 'employees') {
        return $isSuperAdmin || $isActiveEmployee;
    }
    if ($group === 'interns') {
        return $isSuperAdmin || $isActiveEmployee || $isOngoingIntern;
    }

    return false;
});