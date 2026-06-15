<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\IssueParticipant;
use App\Support\Issues\IssueRowLock;

class ReparentOnCanonicalDelete
{
    /**
     * Delete a canonical issue, promoting the oldest child when duplicates exist.
     *
     * With no children the canonical is hard-deleted (participants cascade via FK).
     * Otherwise the oldest child (`created_at ASC`, `id ASC`) becomes the new
     * canonical, siblings are re-parented, participants migrate from the old
     * canonical, counters are recalculated, and the old canonical is removed.
     */
    public function delete(Issue $canonical): void
    {
        IssueRowLock::withLockedIssue($canonical, function (Issue $lockedCanonical): void {
            $children = Issue::query()
                ->where('duplicate_of_id', $lockedCanonical->getKey())
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            if ($children->isEmpty()) {
                $lockedCanonical->delete();

                return;
            }

            /** @var Issue $promoted */
            $promoted = $children->first();
            $remaining = $children->slice(1);

            $promoted->update(['duplicate_of_id' => null]);

            foreach ($remaining as $sibling) {
                $sibling->update(['duplicate_of_id' => $promoted->getKey()]);
            }

            $this->migrateParticipants($lockedCanonical, $promoted);

            $promoted->update([
                'duplicate_count' => Issue::query()
                    ->where('duplicate_of_id', $promoted->getKey())
                    ->count(),
                'participant_count' => IssueParticipant::query()
                    ->where('issue_id', $promoted->getKey())
                    ->count(),
            ]);

            $lockedCanonical->delete();
        });
    }

    /**
     * Move participant rows from the old canonical to the promoted issue.
     *
     * When a user already participates on the promoted issue, the old row is
     * discarded to satisfy the unique `(issue_id, user_id)` constraint.
     */
    private function migrateParticipants(Issue $oldCanonical, Issue $promoted): void
    {
        $participants = IssueParticipant::query()
            ->where('issue_id', $oldCanonical->getKey())
            ->get();

        foreach ($participants as $participant) {
            $existsOnPromoted = IssueParticipant::query()
                ->where('issue_id', $promoted->getKey())
                ->where('user_id', $participant->user_id)
                ->exists();

            if ($existsOnPromoted) {
                $participant->delete();

                continue;
            }

            $participant->update(['issue_id' => $promoted->getKey()]);
        }
    }
}
