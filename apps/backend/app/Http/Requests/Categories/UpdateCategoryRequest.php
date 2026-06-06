<?php

namespace App\Http\Requests\Categories;

use App\Models\Category;
use App\Models\Manager;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may update categories.
     *
     * Mirrors StoreCategoryRequest: users, officers, and inactive managers are
     * rejected with a 403 response.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true;
    }

    /**
     * Validation rules for category updates.
     *
     * All mutable fields use `sometimes` so a partial update only validates and
     * applies the provided keys. Hierarchy, priority/weight, and department
     * rules match StoreCategoryRequest, with the additional guards that a
     * category cannot become its own parent and a category that already has
     * subcategories cannot be demoted into a subcategory itself. Activation and
     * deactivation use `is_active` on this request only; there is no dedicated
     * `/disable` route.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                $this->parentRule(),
            ],
            'department_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')],
            'priority' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:255',
                Rule::prohibitedIf(fn (): bool => $this->resolvesToSubcategory()),
            ],
            'weight' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:255',
                Rule::prohibitedIf(fn (): bool => ! $this->resolvesToSubcategory()),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Reject an invalid `parent_id` for the category being updated.
     *
     * Enforces that the parent (when provided) is not the category itself, is
     * an active main category, and that demoting a category which already has
     * its own subcategories is not allowed (this would create nesting).
     */
    protected function parentRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $category = $this->route('category');
            $parentId = (int) $value;

            if ($category instanceof Category && $category->getKey() === $parentId) {
                $fail('A category cannot be its own parent.');

                return;
            }

            if ($category instanceof Category && $category->children()->exists()) {
                $fail('A category with existing subcategories cannot become a subcategory.');

                return;
            }

            $parent = Category::query()->find($parentId);

            if (! $parent instanceof Category) {
                // The `exists` rule reports the missing parent.
                return;
            }

            if ($parent->parent_id !== null) {
                $fail('The selected parent must be a main category; nested subcategories are not allowed.');

                return;
            }

            if (! (bool) $parent->is_active) {
                $fail('The selected parent category must be active.');
            }
        };
    }

    /**
     * Determine whether the category will be a subcategory after this update.
     *
     * When `parent_id` is present in the request payload, the incoming value
     * decides; otherwise the existing category's current parent is used. This
     * lets the prohibited priority/weight rules apply to the resulting state.
     */
    protected function resolvesToSubcategory(): bool
    {
        if ($this->exists('parent_id')) {
            return $this->filled('parent_id');
        }

        $category = $this->route('category');

        return $category instanceof Category && $category->parent_id !== null;
    }

    /**
     * Custom validation messages for the prohibited priority/weight fields.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'priority.prohibited' => 'The priority field is only allowed for main categories.',
            'weight.prohibited' => 'The weight field is only allowed for subcategories.',
        ];
    }

    /**
     * The department ids the category should be assigned to, when provided.
     *
     * @return array<int, int>
     */
    public function departmentIds(): array
    {
        return array_map('intval', $this->input('department_ids', []));
    }
}
