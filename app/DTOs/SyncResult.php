<?php

namespace App\DTOs;

readonly class SyncResult
{
    public function __construct(
        public int $newStreams,
        public int $updatedStreams,
        public int $endedStreams,
        public int $alertsTriggered,
        public float $durationSeconds,
    ) {}
}
