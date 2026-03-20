<?php

declare (strict_types=1);
namespace Pest\Arch_Presets;

/**
 * @internal
 */
final class Security extends Abstract_Preset
{
    /**
     * Executes the arch preset.
     */
    public function execute(): void
    {
        $this->expectations[] = expect(['md5', 'sha1', 'uniqid', 'rand', 'mt_rand', 'tempnam', 'str_shuffle', 'shuffle', 'array_rand', 'eval', 'exec', 'shell_exec', 'system', 'passthru', 'create_function', 'unserialize', 'extract', 'mb_parse_str', 'dl', 'assert'])->not->to_be_used();
    }
}