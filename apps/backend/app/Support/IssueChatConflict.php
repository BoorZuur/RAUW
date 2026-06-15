<?php

namespace App\Support;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class IssueChatConflict extends Exception implements HttpExceptionInterface
{
    public $code;

    public function __construct(
        string $code,
        string $message,
        public readonly int $status,
    ) {
        parent::__construct($message);
        $this->code = $code;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public static function chatClosed(
        string $message = 'Cannot send or mark messages read while the chat is closed.',
    ): self {
        return new self(
            code: 'chat_closed',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function chatUserNotEligible(
        string $message = 'The selected user is not eligible for a chat on this issue.',
    ): self {
        return new self(
            code: 'chat_user_not_eligible',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function notChatParticipant(
        string $message = 'You are not a participant in this chat.',
    ): self {
        return new self(
            code: 'not_chat_participant',
            message: $message,
            status: Response::HTTP_FORBIDDEN,
        );
    }

    public static function issueClosed(
        string $message = 'Cannot open a chat on a closed issue.',
    ): self {
        return new self(
            code: 'issue_closed',
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function chatNotFound(
        string $message = 'Chat not found for this issue.',
    ): self {
        return new self(
            code: 'chat_not_found',
            message: $message,
            status: Response::HTTP_NOT_FOUND,
        );
    }
}
