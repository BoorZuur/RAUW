<?php

namespace App\Actions\Auth;

use App\Enums\ActorType;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;

class ResolveLoginActor
{
    /**
     * Deterministic search order across actor models. Users are checked first
     * because they represent the majority of accounts, followed by officers
     * and finally managers.
     *
     * @var array<int, array{type: ActorType, model: class-string<Model>}>
     */
    private const ACTOR_SOURCES = [
        ['type' => ActorType::User, 'model' => User::class],
        ['type' => ActorType::Officer, 'model' => Officer::class],
        ['type' => ActorType::Manager, 'model' => Manager::class],
    ];

    public function __construct(private readonly Hasher $hasher)
    {
    }

    /**
     * Resolve an authenticated actor from a shared email/password pair.
     *
     * Returns the matched model plus its actor type. Throws a generic
     * AuthenticationException for unknown emails, wrong passwords, inactive
     * accounts, or ambiguous matches so callers cannot infer account state.
     *
     * @return array{actor: Authenticatable&Model, type: ActorType}
     *
     * @throws AuthenticationException
     */
    public function resolve(string $email, string $password): array
    {
        $matches = [];

        foreach (self::ACTOR_SOURCES as $source) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $source['model'];

            /** @var (Authenticatable&Model)|null $actor */
            $actor = $modelClass::query()->where('email', $email)->first();

            if ($actor === null) {
                continue;
            }

            $matches[] = ['actor' => $actor, 'type' => $source['type']];
        }

        // Ambiguous: same email registered against multiple actor tables.
        if (count($matches) !== 1) {
            throw $this->failure();
        }

        $match = $matches[0];
        /** @var Authenticatable&Model $actor */
        $actor = $match['actor'];

        $hashedPassword = (string) $actor->getAuthPassword();

        if ($hashedPassword === '' || ! $this->hasher->check($password, $hashedPassword)) {
            throw $this->failure();
        }

        if (array_key_exists('is_active', $actor->getAttributes()) && ! (bool) $actor->getAttribute('is_active')) {
            throw $this->failure();
        }

        return $match;
    }

    private function failure(): AuthenticationException
    {
        return new AuthenticationException('Invalid credentials.');
    }
}
