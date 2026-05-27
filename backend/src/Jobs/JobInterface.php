<?php

namespace App\Jobs;

interface JobInterface
{
    public function handle(array $data): void;
}
