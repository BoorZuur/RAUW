<?php

namespace App\Http\Requests\Categories;

use App\Models\Category;
use App\Models\Manager;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Only an authenticated, active manager may create categories.
     *
     * The authenticated actor is resolved from the Sanctum bearer token and
     * may be a User, Officer, or Manager. Creation is restricted to a Manager
     * whose `is_active` flag is true, so users, officers, and inactive
     * managers are all rejected with a 403 response. Category management is
     * open to ordinary managers (not just main managers).
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof Manager
            && (bool) $actor->is_active === true;
    }

    /**
     * Validation rules for category creation.
     *
     * Hierarchy: main categories have no `parent_id` and order themselves with
     * the general `priority` field; subcategories reference an existing active
     * main category and order themselves with `weight`. Nested subcategories
     * (a parent that is itself a subcategory) are rejected. Both `priority` and
     * `weight` use a lower-number-is-higher-priority ordering. Each category is
     * assigned to one or more existing departments through `department_ids`.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                $this->mainCategoryParentRule(),
            ],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', Rule::exists('departments', 'id')],
            // The general priority applies to main categories only; it must be
            // omitted for subcategories, which order themselves via `weight`.
            'priority' => [
                'nullable',
                'integer',
                'min:0',
                'max:255',
                Rule::prohibitedIf(fn (): bool => $this->filled('parent_id')),
            ],
            // The weight applies to subcategories only; it must be omitted for
            // main categories, which order themselves via `priority`.
            'weight' => [
                'nullable',
                'integer',
                'min:0',
                'max:255',
                Rule::prohibitedIf(fn (): bool => ! $this->filled('parent_id')),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Reject a `parent_id` that does not reference an active main category.
     *
     * The `exists` rule already guarantees the parent row exists; this closure
     * additionally enforces that the parent is a main category (its own
     * `parent_id` is null) and is currently active, preventing nested
     * subcategories and attachment to disabled parents.
     */
    protected function mainCategoryParentRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $parent = Category::query()->find($value);

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
     * The department ids the category should be assigned to.
     *
     * @return array<int, int>
     */
    public function departmentIds(): array
    {
        return array_map('intval', $this->input('department_ids', []));
    }
}
