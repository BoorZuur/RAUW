<?php

namespace App\Http\Requests\DepartmentAssignments;

use App\Http\Requests\DepartmentAssignments\Concerns\AssignsActorDepartments;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOfficerDepartmentsRequest extends FormRequest
{
    use AssignsActorDepartments;

    /**
     * Only an authenticated, active main manager may update an officer's department
     * assignments.
     *
     * Users, officers, non-main managers, inactive managers, and inactive main
     * managers are all rejected with a 403 response. This differs from officer
     * district assignment, which any active manager may perform.
     *
     * `department_ids` must be present so the request declares the full desired
     * assignment set; an empty array is allowed to clear all departments. Every
     * non-empty entry must reference an existing department row.
     */
}
