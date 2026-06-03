<?php

namespace App\Http\Requests\Auth;

use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use App\Rules\UniqueActorEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Only an authenticated, active user, officer, or manager may update their
     * own identity fields through this endpoint.
     *
     * Inactive actors cannot authenticate, but the active flag is asserted here
     * as a defence-in-depth guard.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return ($actor instanceof User || $actor instanceof Officer || $actor instanceof Manager)
            && (bool) $actor->is_active === true;
    }

    /**
     * Partial PATCH validation for self-service profile identity updates.
     *
     * Username uniqueness is checked against the actor's own table column so
     * that soft-deleted rows still reserve their identifiers. Email must remain
     * unique across all actor tables so shared login credentials cannot become
     * ambiguous, ignoring the current actor's row when checking their table.
     * System-managed fields (is_active, flag_count, is_under_review,
     * email_verified_at, remember_token, deleted_at, department_ids,
     * district_ids, is_main_manager, created_by_manager_id, and tokens) are
     * never accepted from the client.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $table = $this->actorTable();
        $id = $this->actorId();
        $actor = $this->user();

        $rules = [
            'username' => ['sometimes', 'string', 'max:50', Rule::unique($table, 'username')->ignore($id)],
            'email' => ['sometimes', 'email', new UniqueActorEmail($table, $id)],
            'password' => ['sometimes', 'string', Password::min(8)],
            'confirm_password' => ['required_with:password', 'string', 'same:password'],
            'department_ids' => ['prohibited'],
            'district_ids' => ['prohibited'],
            'is_main_manager' => ['prohibited'],
            'is_active' => ['prohibited'],
            'flag_count' => ['prohibited'],
            'is_under_review' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'remember_token' => ['prohibited'],
            'created_by_manager_id' => ['prohibited'],
        ];

        if ($actor instanceof Officer) {
            $rules['badge_number'] = ['sometimes', 'string', 'max:20', Rule::unique('officers', 'badge_number')->ignore($id)];
        } else {
            $rules['badge_number'] = ['prohibited'];
        }

        return $rules;
    }

    /**
     * Validated model attributes to persist, excluding confirm_password.
     *
     * Only keys present in the request and that passed validation are returned.
     * Password is returned in plain text so the model's hashed cast can apply.
     *
     * @return array<string, mixed>
     */
    public function changedAttributes(): array
    {
        return collect($this->validated())
            ->except('confirm_password')
            ->all();
    }

    /**
     * The authenticated actor's backing table name.
     */
    private function actorTable(): string
    {
        $actor = $this->user();

        return match (true) {
            $actor instanceof User => 'users',
            $actor instanceof Officer => 'officers',
            $actor instanceof Manager => 'managers',
            default => 'users',
        };
    }

    /**
     * The authenticated actor's primary key.
     */
    private function actorId(): int
    {
        return (int) $this->user()->getKey();
    }
}
