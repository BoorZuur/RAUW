<?php

namespace App\Actions\Issues;

use App\Enums\IssueMessageSenderType;
use App\Enums\IssueMessageType;
use App\Models\Issue;
use App\Models\IssueChat;
use App\Models\IssueMessage;
use App\Models\Officer;
use App\Models\User;
use App\Support\Issues\IssueChatAccess;
use App\Support\Issues\IssueChatMessageAttachments;
use App\Support\UploadedFileValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendIssueMessage
{
    private const MAX_CONTENT_LENGTH = 2000;

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function send(
        Model $actor,
        Issue $canonical,
        IssueChat $chat,
        ?string $content,
        array $files = [],
    ): IssueMessage {
        IssueChatAccess::assertCanSendInChat($actor, $chat, $canonical);

        $normalizedContent = $this->normalizeContent($content);
        $this->validatePayload($normalizedContent, $files);

        $senderType = $actor instanceof Officer
            ? IssueMessageSenderType::Officer
            : IssueMessageSenderType::User;

        $pathsWrittenDuringRequest = [];

        try {
            $message = $chat->messages()->create([
                'issue_id' => $canonical->getKey(),
                'message_type' => IssueMessageType::Message,
                'sender_type' => $senderType,
                'user_id' => $actor instanceof User ? $actor->getKey() : null,
                'officer_id' => $actor instanceof Officer ? $actor->getKey() : null,
                'content' => $normalizedContent,
                'meta' => null,
                'is_read' => false,
            ]);

            if ($files !== []) {
                $pathsWrittenDuringRequest = $this->attachUploadedFiles($chat, $message, $files);
            }

            return $message->fresh(['attachments']);
        } catch (Throwable $exception) {
            self::deleteDiskFiles($pathsWrittenDuringRequest);

            throw $exception;
        }
    }

    private function normalizeContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $trimmed = trim($content);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function validatePayload(?string $content, array $files): void
    {
        if ($content === null && $files === []) {
            throw ValidationException::withMessages([
                'content' => ['A message requires text content or at least one attachment.'],
                'files' => ['A message requires text content or at least one attachment.'],
            ]);
        }

        if ($content !== null && mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            throw ValidationException::withMessages([
                'content' => [
                    'The content field must not be greater than '.self::MAX_CONTENT_LENGTH.' characters.',
                ],
            ]);
        }

        if (count($files) > IssueChatMessageAttachments::MAX_COUNT) {
            throw ValidationException::withMessages([
                'files' => [
                    'A message may have at most '.IssueChatMessageAttachments::MAX_COUNT.' attachments.',
                ],
            ]);
        }

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            if ($file->getSize() > IssueChatMessageAttachments::MAX_FILE_SIZE_KB * 1024) {
                throw ValidationException::withMessages([
                    "files.{$index}" => [
                        'The file must not be greater than '.IssueChatMessageAttachments::MAX_FILE_SIZE_KB.' kilobytes.',
                    ],
                ]);
            }

            try {
                UploadedFileValidator::assertAllowedImage($file);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    "files.{$index}" => [$exception->getMessage()],
                ]);
            }
        }
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    private function attachUploadedFiles(IssueChat $chat, IssueMessage $message, array $files): array
    {
        $writtenPaths = [];

        foreach ($files as $file) {
            $path = $file->store(
                IssueChatMessageAttachments::DIRECTORY.'/'.$chat->getKey(),
                IssueChatMessageAttachments::DISK,
            );
            $writtenPaths[] = $path;

            try {
                $message->attachments()->create([
                    'file_path' => $path,
                    'file_url' => null,
                    'original_name' => $file->getClientOriginalName(),
                    'file_type' => UploadedFileValidator::detectMimeType($file),
                    'file_size' => $file->getSize(),
                    'uploaded_at' => Carbon::now(),
                ]);
            } catch (Throwable $exception) {
                self::deleteDiskFiles($writtenPaths);

                throw $exception;
            }
        }

        return $writtenPaths;
    }

    /**
     * @param  array<int, string>  $paths
     */
    private static function deleteDiskFiles(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $disk = Storage::disk(IssueChatMessageAttachments::DISK);

        foreach ($paths as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
