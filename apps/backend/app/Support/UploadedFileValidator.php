<?php

namespace App\Support;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class UploadedFileValidator
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Detect MIME type from file content (not the client-supplied header).
     */
    public static function detectMimeType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }

    /**
     * Require a content-sniffed image MIME in the allowed set.
     */
    public static function assertAllowedImage(UploadedFile $file): void
    {
        $mime = self::detectMimeType($file);

        if (! in_array($mime, self::ALLOWED_IMAGE_MIMES, true)) {
            throw new InvalidArgumentException(
                'The file must be a valid image (JPEG, PNG, GIF, or WebP).',
            );
        }
    }

    /**
     * Require a PDF magic header (%PDF-) in the first bytes of the file.
     */
    public static function assertAllowedPdf(UploadedFile $file): void
    {
        if (! self::hasPdfMagicHeader($file)) {
            throw new InvalidArgumentException('The file must be a valid PDF.');
        }
    }

    /**
     * Require either a content-sniffed allowed image or a PDF with magic bytes.
     */
    public static function assertAllowedIssueAttachment(UploadedFile $file): void
    {
        $mime = self::detectMimeType($file);

        if (in_array($mime, self::ALLOWED_IMAGE_MIMES, true)) {
            self::assertAllowedImage($file);

            return;
        }

        if ($mime === 'application/pdf' || self::hasPdfMagicHeader($file)) {
            self::assertAllowedPdf($file);

            return;
        }

        throw new InvalidArgumentException(
            'The file must be a valid image (JPEG, PNG, GIF, or WebP) or PDF.',
        );
    }

    /**
     * Run a per-file assertion against each entry in a files array after rules pass.
     *
     * @param  array<int, mixed>|null  $files
     */
    public static function validateUploadedFiles(
        Validator $validator,
        ?array $files,
        callable $assert,
    ): void {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        if (! is_array($files)) {
            return;
        }

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            try {
                $assert($file);
            } catch (InvalidArgumentException $exception) {
                $validator->errors()->add("files.{$index}", $exception->getMessage());
            }
        }
    }

    private static function hasPdfMagicHeader(UploadedFile $file): bool
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $header = fread($handle, 5);
        } finally {
            fclose($handle);
        }

        return $header === '%PDF-';
    }
}
