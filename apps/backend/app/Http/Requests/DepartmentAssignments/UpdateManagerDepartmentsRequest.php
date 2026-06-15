<?php

namespace App\Http\Requests\DepartmentAssignments;

use App\Http\Requests\DepartmentAssignments\Concerns\AssignsActorDepartments;
use Illuminate\Foundation\Http\FormRequest;

class UpdateManagerDepartmentsRequest extends FormRequest
{
    use AssignsActorDepartments;

    /**
     * Only an authenticated, active main manager may update a manager's department
     * assignments.
     *
     * A main manager may update any manager, including other main managers.
     * Users, officers, non-main managers, inactive managers, and inactive main
     * managers receive a 403 response.
     *
     * `department_ids` must be present; an empty array clears all department
     * assignments. Every non-empty entry must reference an existing department row.
     */
}
