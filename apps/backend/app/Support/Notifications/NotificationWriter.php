<?php

namespace App\Support\Notifications;

use App\Enums\ActorType;
use App\Models\DomainNotification;
use App\Models\Manager;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationWriter
{
    public function write(NotificationInsert $insert): ?DomainNotification
    {
        if (! config('notifications.enabled')) {
            return null;
        }

        if ($insert->dedupKey !== null) {
            $inserted = $this->insertRowsWithDedup([$insert->toRowArray()]);

            if ($inserted === 0) {
                return null;
            }

            return DomainNotification::query()
                ->where('dedup_key', $insert->dedupKey)
                ->first();
        }

        $notification = new DomainNotification($this->rowForModel($insert->toRowArray()));
        $notification->save();

        return $notification;
    }

    /**
     * @param  Collection<int, NotificationInsert>  $inserts
     */
    public function writeMany(Collection $inserts): int
    {
        if (! config('notifications.enabled') || $inserts->isEmpty()) {
            return 0;
        }

        $prepared = $this->deduplicateInMemory($inserts);

        $withDedupKey = $prepared
            ->filter(static fn (NotificationInsert $insert): bool => $insert->dedupKey !== null)
            ->values();

        $withoutDedupKey = $prepared
            ->filter(static fn (NotificationInsert $insert): bool => $insert->dedupKey === null)
            ->values();

        $inserted = 0;

        if ($withDedupKey->isNotEmpty()) {
            $inserted += $this->insertRowsWithDedup(
                $withDedupKey
                    ->map(static fn (NotificationInsert $insert): array => $insert->toRowArray())
                    ->all(),
            );
        }

        if ($withoutDedupKey->isNotEmpty()) {
            $inserted += $this->insertRows(
                $withoutDedupKey
                    ->map(static fn (NotificationInsert $insert): array => $insert->toRowArray())
                    ->all(),
            );
        }

        return $inserted;
    }

    /**
     * @param  Collection<int, NotificationInsert>  $inserts
     * @return Collection<int, NotificationInsert>
     */
    public function excludeActor(Collection $inserts, Model $actor): Collection
    {
        $actorType = match (true) {
            $actor instanceof User => ActorType::User,
            $actor instanceof Officer => ActorType::Officer,
            $actor instanceof Manager => ActorType::Manager,
            default => null,
        };

        if ($actorType === null) {
            return $inserts->values();
        }

        $actorId = (int) $actor->getKey();

        return $inserts
            ->reject(static function (NotificationInsert $insert) use ($actorType, $actorId): bool {
                return $insert->recipientType === $actorType
                    && $insert->recipientId() === $actorId;
            })
            ->values();
    }

    /**
     * @param  Collection<int, NotificationInsert>  $inserts
     * @return Collection<int, NotificationInsert>
     */
    private function deduplicateInMemory(Collection $inserts): Collection
    {
        $seenKeys = [];

        return $inserts
            ->filter(function (NotificationInsert $insert) use (&$seenKeys): bool {
                if ($insert->dedupKey === null) {
                    return true;
                }

                if (isset($seenKeys[$insert->dedupKey])) {
                    return false;
                }

                $seenKeys[$insert->dedupKey] = true;

                return true;
            })
            ->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function insertRowsWithDedup(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        return DB::table($this->tableName())->insertOrIgnore($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function insertRows(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        DB::table($this->tableName())->insert($rows);

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function rowForModel(array $row): array
    {
        if (is_string($row['payload'])) {
            $row['payload'] = json_decode($row['payload'], true);
        }

        return $row;
    }

    private function tableName(): string
    {
        return (new DomainNotification)->getTable();
    }
}
