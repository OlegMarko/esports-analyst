<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;

class PlayerAnalystAgent implements Agent, Conversational
{
    use Promptable;

    public function provider(): string
    {
        return (string) config('ai.agent_provider', 'ollama');
    }

    public function temperature(): float
    {
        return 0.4;
    }

     public function maxTokens(): int
    {
        return (int) config('ai.agent_max_tokens', 2000);
    }

    public function instructions(): string
    {
        return 'You are a CS2 esports analyst. Write a concise 2-3 sentence performance brief for a player '
            . 'based on their recent match stats. Be specific — cite maps, kill counts, KDA values, and win rates. '
            . 'Identify clear strengths, patterns, or areas of concern. No generic phrases like "solid performer".';
    }

    public function messages(): iterable
    {
        return [];
    }
}
