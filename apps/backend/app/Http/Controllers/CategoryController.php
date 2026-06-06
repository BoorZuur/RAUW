<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\DeleteCategoryRequest;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * List categories ordered by priority (main) then weight (subcategory).
     *
     * Departments and parent are eager loaded to avoid N+1 queries, and main
     * categories carry their nested children. A LOWER `priority`/`weight`
     * value sorts first (higher priority); null values sort last so explicitly
     * ordered categories appear ahead of unordered ones.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->with([
                'departments',
                'parent',
                'children' => fn ($query) => $query
                    ->with('departments')
                    ->orderByRaw('weight IS NULL')
                    ->orderBy('weight')
                    ->orderBy('id'),
            ])
            ->whereNull('parent_id')
            ->orderByRaw('priority IS NULL')
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Show a single category with its departments, parent, and children.
     */
    public function show(Category $category): CategoryResource
    {
        $category->load([
            'departments',
            'parent',
            'children' => fn ($query) => $query
                ->with('departments')
                ->orderByRaw('weight IS NULL')
                ->orderBy('weight')
                ->orderBy('id'),
        ]);

        return new CategoryResource($category);
    }

    /**
     * Create a category and attach its departments.
     *
     * Authorization and hierarchy/priority validation are enforced by
     * StoreCategoryRequest. The category and its pivot assignments are written
     * in a single transaction so a failed attach never leaves an orphan row.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = DB::transaction(function () use ($request): Category {
            $category = Category::create($request->safe()->only([
                'name',
                'parent_id',
                'priority',
                'weight',
                'is_active',
            ]));

            $category->departments()->sync($request->departmentIds());

            return $category;
        });

        $category->load(['departments', 'parent']);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a category and, when provided, re-sync its departments.
     *
     * Only the validated keys present in the request are applied, so partial
     * updates leave untouched fields intact. Departments are only re-synced
     * when `department_ids` is included in the payload.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        DB::transaction(function () use ($request, $category): void {
            $attributes = $request->safe()->only([
                'name',
                'parent_id',
                'priority',
                'weight',
                'is_active',
            ]);

            if ($attributes !== []) {
                $category->update($attributes);
            }

            if ($request->has('department_ids')) {
                $category->departments()->sync($request->departmentIds());
            }
        });

        $category->refresh()->load(['departments', 'parent']);

        return new CategoryResource($category);
    }

    /**
     * Hard delete an eligible category.
     *
     * A main category that still has subcategories is rejected with a 409 so
     * the hierarchy is never orphaned. The pivot rows are removed by the
     * database cascade, but any `issues.category_id` reference uses
     * `restrictOnDelete`; that constraint failure is caught and returned as a
     * 409 conflict rather than surfacing as a 500.
     */
    public function destroy(DeleteCategoryRequest $request, Category $category): JsonResponse
    {
        if ($category->parent_id === null && $category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a main category while it still has subcategories.',
            ], Response::HTTP_CONFLICT);
        }

        try {
            $category->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Cannot delete this category because it is still referenced by existing records. Disable it instead.',
            ], Response::HTTP_CONFLICT);
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
