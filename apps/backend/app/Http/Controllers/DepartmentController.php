<?php

namespace App\Http\Controllers;

use App\Http\Requests\Departments\StoreDepartmentRequest;
use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Manager;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Department::query()
            ->withCount('categories')
            ->orderBy('code');

        if (! $this->canViewInactiveDepartments($request)) {
            $query->where('is_active', true);
        }

        return DepartmentResource::collection($query->get());
    }

    /**
     * Show a single department with its category count.
     */
    public function show(Request $request, Department $department): DepartmentResource
    {
        if (! $department->is_active && ! $this->canViewInactiveDepartments($request)) {
            abort(404);
        }

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
    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource|JsonResponse
    {
        $attributes = $request->safe()->only([
            'code',
            'name',
            'is_active',
        ]);

        if ($attributes !== []) {
            if (array_key_exists('is_active', $attributes) && $attributes['is_active'] === false) {
                try {
                    $department->update($attributes);
                } catch (QueryException $exception) {
                    return response()->json([
                        'message' => 'Cannot disable this department because it is still referenced by existing records.',
                    ], Response::HTTP_CONFLICT);
                }
            } else {
                $department->update($attributes);
            }
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
     * still assigned to any manager or officer through the `department_manager`
     * or `department_officer` pivots, because managers and officers must each
     * have one or more departments: removing the row would orphan those actors
     * and break the at-least-one-department invariant. Once no actor references
     * the department, deleting it relies on the `category_department` pivot's
     * `cascadeOnDelete` foreign keys to remove the category assignments
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

    private function canViewInactiveDepartments(Request $request): bool
    {
        $user = $request->user('sanctum');

        return $user instanceof Manager
            && $user->is_active
            && $user->is_main_manager;
    }
}
