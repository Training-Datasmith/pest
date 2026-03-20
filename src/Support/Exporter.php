<?php

declare (strict_types=1);
namespace Pest\Support;

use Sebastian_Bergmann\Exporter\Exporter as BaseExporter;
use Sebastian_Bergmann\Recursion_Context\Context;
/**
 * @internal
 */
final readonly class Exporter
{
    /**
     * The maximum number of items in an array to export.
     */
    private const int MAX_ARRAY_ITEMS = 3;
    /**
     * Creates a new Exporter instance.
     */
    public function __construct(private Base_Exporter $exporter)
    {
        // ...
    }
    /**
     * Creates a new Exporter instance.
     */
    public static function default(): self
    {
        return new self(new Base_Exporter());
    }
    /**
     * Exports a value into a single-line string recursively.
     *
     * @param  array<int|string, mixed>  $data
     */
    public function shortened_recursive_export(array &$data, ?Context $context = null): string
    {
        $result = [];
        $array = $data;
        $items_count = 0;
        $exporter = self::default();
        $context ??= new Context();
        $context->add($data);
        foreach ($array as $key => $value) {
            if (++$items_count > self::MAX_ARRAY_ITEMS) {
                $result[] = '…';
                break;
            }
            if (!is_array($value)) {
                $result[] = $exporter->shortened_export($value);
                continue;
            }
            $result[] = $context->contains($data[$key]) !== false ? '*RECURSION*' : sprintf('[%s]', $this->shortened_recursive_export($data[$key], $context));
        }
        return implode(', ', $result);
    }
    /**
     * Exports a value into a single-line string.
     */
    public function shortened_export(mixed $value): string
    {
        $map = ['#\.{3}#' => '…', '#\\\\n\s*#' => '', '# Object \(…\)#' => ''];
        return (string) preg_replace(array_keys($map), array_values($map), $this->exporter->shortened_export($value));
    }
}