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
     * Any authenticated user, officer, or manager may update their own profile.
     * Active actors may change username, email, and password (officers also
     * badge_number). Inactive actors may only change username and password for
     * account recovery; {@see EnsureActorIsActive} whitelists this route.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
            || $actor instanceof Officer
            || $actor instanceof Manager;
    }

    /**
     * Partial PATCH validation for self-service profile identity updates.
     *
     * Username uniqueness is checked against the actor's own table column so
     * that soft-deleted rows still reserve their identifiers. Active actors may
     * update email (unique across all actor tables) and officers may update
     * badge_number. Inactive actors may only update username and password.
     * System-managed fields (is_active, flag_count, is_under_review,
     * email_verified_at, remember_token, deleted_at, department_ids,
     * district_ids, hub_id, hub_active_until, is_main_manager,
     * created_by_manager_id, and tokens) are never accepted from the client.
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
            'password' => ['sometimes', 'string', Password::min(8)],
            'confirm_password' => ['required_with:password', 'string', 'same:password'],
            'department_ids' => ['prohibited'],
            'district_ids' => ['prohibited'],
            'hub_id' => ['prohibited'],
            'hub_active_until' => ['prohibited'],
            'is_main_manager' => ['prohibited'],
            'is_active' => ['prohibited'],
            'flag_count' => ['prohibited'],
            'is_under_review' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'remember_token' => ['prohibited'],
            'created_by_manager_id' => ['prohibited'],
        ];

        if ($this->isActorActive()) {
            $rules['email'] = ['sometimes', 'email', new UniqueActorEmail($table, $id)];

            if ($actor instanceof Officer) {
                $rules['badge_number'] = ['sometimes', 'string', 'max:20', Rule::unique('officers', 'badge_number')->ignore($id)];
            } else {
                $rules['badge_number'] = ['prohibited'];
            }
        } else {
            $rules['email'] = ['prohibited'];
            $rules['badge_number'] = ['prohibited'];
        }

        return $rules;
    }

    private function isActorActive(): bool
    {
        return (bool) $this->user()->is_active;
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
