<?php

namespace Modules\Recovery\Exceptions;

/**
 * A rejected piece of a chunked archive upload.
 *
 * $retryable tells the browser whether sending the same piece again can
 * succeed (a checksum mismatch after a flaky transfer, a short write when the
 * disk filled) or whether the upload itself is broken and the page has to be
 * reloaded (out-of-order pieces, an oversized archive, a bad file name).
 */
class ChunkUploadException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = false,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }
}
