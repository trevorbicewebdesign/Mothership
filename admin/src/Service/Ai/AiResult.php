<?php

namespace TrevorBice\Component\Mothership\Administrator\Service\Ai;

\defined('_JEXEC') or die;

/**
 * Provider-agnostic result of an AI completion.
 *
 * A connector plugin returns a plain array (see AiService::chat); this wraps it
 * so callers get a predictable object regardless of which provider answered.
 */
class AiResult
{
    /** Whether the completion succeeded. */
    public bool $ok = false;

    /** The assistant's text output (empty string on failure). */
    public string $text = '';

    /** Why generation stopped (provider-specific, e.g. "end_turn", "refusal"). */
    public ?string $stopReason = null;

    /** Token usage, if the provider reported it (e.g. ['input' => 12, 'output' => 34]). */
    public array $usage = [];

    /** The model that answered, as the provider named it. */
    public ?string $model = null;

    /** Human-readable error message when $ok is false. */
    public ?string $error = null;

    /** The raw decoded provider response, for advanced callers. */
    public array $raw = [];

    /**
     * Builds a result from the array a connector plugin returns.
     */
    public static function fromArray(array $data): self
    {
        $r = new self();
        $r->ok         = (bool) ($data['ok'] ?? false);
        $r->text       = (string) ($data['text'] ?? '');
        $r->stopReason = $data['stop_reason'] ?? null;
        $r->usage      = (array) ($data['usage'] ?? []);
        $r->model      = $data['model'] ?? ($data['raw']['model'] ?? null);
        $r->error      = $data['error'] ?? null;
        $r->raw        = (array) ($data['raw'] ?? []);

        return $r;
    }

    /**
     * Builds a failed result with an error message.
     */
    public static function fail(string $error): self
    {
        $r = new self();
        $r->ok    = false;
        $r->error = $error;

        return $r;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
