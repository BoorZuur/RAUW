<?php

namespace App\Support\Issues;

final class IssueChatMessageAttachments
{
    public const MAX_COUNT = 3;

    public const MAX_FILE_SIZE_KB = 5120;

    public const DISK = 'local';

    public const DIRECTORY = 'issue-message-attachments';
}
