<?php

namespace App\Services\LLM;

interface LLMServiceInterface
{
    /**
     * Analyze a block of WhatsApp conversation text and decide whether it
     * represents an actionable demand.
     *
     * Always returns the shape:
     * [
     *     'is_demand' => bool,
     *     'title' => string,
     *     'summary' => string,
     *     'due_date' => string|null,
     * ]
     *
     * @return array{is_demand: bool, title: string, summary: string, due_date: ?string}
     */
    public function analyzeDemand(string $messages): array;
}
