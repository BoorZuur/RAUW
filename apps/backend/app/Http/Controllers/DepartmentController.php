<?php

namespace App\Http\Controllers;

use App\Http\Requests\Departments\StoreDepartmentRequest;
use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    /**
     * List departments ordered by code, each with its category count.
     *
     * The category count is eager-loaded with `withCount` to avoid N+1
     * queries when serializing the collection.
     */
    public function index(): AnonymousResourceCollection
    {
        $departments = Department::query()
            ->withCount('categories')
            ->orderBy('code')
            ->get();

        return DepartmentResource::collection($departments);
    }

    /**
     * Show a single department with its category count.
     */
    public function show(Department $department): DepartmentResource
    {
        $department->loadCount('categories');

        return new DepartmentResource($department);
    }

    /**
     * Create a department.
     *
     * Authorization (active main manager only) and validation are enforced by
     * StoreDepartmentRequest. Only validated attributes are persisted; the
     * `is_active` default is applied by the model when omitted.
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->safe()->only([
            'code',
            'name',
            'is_active',
        ]));

        $department->loadCount('categories');

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a department.
     *
     * Authorization and validation are enforced by UpdateDepartmentRequest.
     * Only the validated keys present in the request are applied, so partial
     * updates leave untouched fields intact.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        $attributes = $request->safe()->only([
            'code',
            'name',
            'is_active',
        ]);

        if ($attributes !== []) {
            $department->update($attributes);
        }

        $department->refresh()->loadCount('categories');

        return new DepartmentResource($department);
    }

    /**
     * Hard delete a department.
     *
     * Authorization (active main manager only) is enforced by the route's
     * UpdateDepartmentRequest-equivalent gate through the dedicated
     * authorization helper below. Deletion is rejected when the department is
     * still assigned to any manager or officer, because managers must have
     * exactly one department and officers must have one or more: removing the
     * row would orphan those actors and break the invariant. Once no actor
     * references the department, deleting it relies on the `category_department`
     * pivot's `cascadeOnDelete` foreign keys to remove the category assignments
     * automatically; the category rows themselves are never touched, so
     * historical issue context is preserved.
     */
    public function destroy(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        if ($department->managers()->exists() || $department->officers()->exists()) {
            return response()->json([
                'message' => 'This department is still assigned to one or more managers or officers and cannot be deleted. Reassign those actors first.',
            ], Response::HTTP_CONFLICT);
        }

        $department->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
