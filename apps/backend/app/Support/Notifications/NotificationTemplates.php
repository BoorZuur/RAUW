<?php

namespace App\Support\Notifications;

use App\Enums\IssueStatus;

class NotificationTemplates
{
    /**
     * @return array{title: string, body: string}
     */
    public static function statusChange(
        string $issueTitle,
        string $actorDisplayName,
        IssueStatus|string $newStatus,
    ): array {
        $statusLabel = self::statusLabel($newStatus);

        return [
            'title' => 'Status gewijzigd',
            'body' => sprintf(
                'De status van melding "%s" is gewijzigd naar %s door %s.',
                $issueTitle,
                $statusLabel,
                $actorDisplayName,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function newMessage(string $issueTitle, string $actorDisplayName): array
    {
        return [
            'title' => 'Nieuw bericht',
            'body' => sprintf(
                '%s heeft een bericht gestuurd over melding "%s".',
                $actorDisplayName,
                $issueTitle,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function newIssue(string $issueTitle, string $actorDisplayName): array
    {
        return [
            'title' => 'Nieuwe melding',
            'body' => sprintf(
                'Er is een nieuwe melding "%s" aangemaakt door %s in uw wijk.',
                $issueTitle,
                $actorDisplayName,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function chatOpened(string $issueTitle, string $actorDisplayName): array
    {
        return [
            'title' => 'Chat geopend',
            'body' => sprintf(
                '%s heeft de chat geopend voor melding "%s".',
                $actorDisplayName,
                $issueTitle,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function chatClosed(string $issueTitle, string $actorDisplayName): array
    {
        return [
            'title' => 'Chat gesloten',
            'body' => sprintf(
                '%s heeft de chat gesloten voor melding "%s".',
                $actorDisplayName,
                $issueTitle,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function newComment(string $issueTitle, string $actorDisplayName): array
    {
        return [
            'title' => 'Nieuw commentaar',
            'body' => sprintf(
                '%s heeft gereageerd op melding "%s".',
                $actorDisplayName,
                $issueTitle,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function resolutionPosted(string $issueTitle, string $actorDisplayName): array
    {
        return [
            'title' => 'Oplossing geplaatst',
            'body' => sprintf(
                '%s heeft een oplossing geplaatst voor melding "%s".',
                $actorDisplayName,
                $issueTitle,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function feedbackReceived(string $issueTitle, string $actorDisplayName): array
    {
        return [
            'title' => 'Feedback ontvangen',
            'body' => sprintf(
                '%s heeft feedback gegeven voor melding "%s".',
                $actorDisplayName,
                $issueTitle,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function newCommunityPost(string $actorDisplayName): array
    {
        return [
            'title' => 'Nieuw bericht in feed',
            'body' => sprintf(
                '%s heeft een nieuw bericht geplaatst in uw wijkfeed.',
                $actorDisplayName,
            ),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function issueHidden(string $issueTitle, string $actorDisplayName): array
    {
        return [
            'title' => 'Melding verborgen',
            'body' => sprintf(
                'Melding "%s" is verborgen door %s.',
                $issueTitle,
                $actorDisplayName,
            ),
        ];
    }

    private static function statusLabel(IssueStatus|string $status): string
    {
        $value = $status instanceof IssueStatus ? $status->value : $status;

        return match ($value) {
            IssueStatus::Open->value => 'open',
            IssueStatus::InProgress->value => 'in behandeling',
            IssueStatus::Resolved->value => 'opgelost',
            IssueStatus::Closed->value => 'gesloten',
            default => $value,
        };
    }
}
