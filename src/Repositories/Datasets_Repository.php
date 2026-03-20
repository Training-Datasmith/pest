<?php

declare (strict_types=1);
namespace Pest\Repositories;

use Closure;
use Generator;
use Pest\Exceptions\Dataset_Already_Exists;
use Pest\Exceptions\Dataset_Does_Not_Exist;
use Pest\Exceptions\Should_Not_Happen;
use Pest\Support\Exporter;
use function sprintf;
use Traversable;
/**
 * @internal
 */
final class Datasets_Repository
{
    private const string SEPARATOR = '>>';
    /**
     * Holds the datasets.
     *
     * @var array<string, Closure|iterable<int|string, mixed>>
     */
    private static array $datasets = [];
    /**
     * Holds the withs.
     *
     * @var array<array<string, Closure|iterable<int|string, mixed>|string>>
     */
    private static array $withs = [];
    /**
     * Sets the given.
     *
     * @param  Closure|iterable<int|string, mixed>  $data
     */
    public static function set(string $name, Closure|iterable $data, string $scope): void
    {
        $dataset_key = "{$scope}" . self::SEPARATOR . "{$name}";
        if (array_key_exists("{$dataset_key}", self::$datasets)) {
            throw new Dataset_Already_Exists($name, $scope);
        }
        self::$datasets[$dataset_key] = $data;
    }
    /**
     * Sets the given "with".
     *
     * @param  array<Closure|iterable<int|string, mixed>|string>  $with
     */
    public static function with(string $filename, string $description, array $with): void
    {
        self::$withs["{$filename}" . self::SEPARATOR . "{$description}"] = $with;
    }
    public static function has(string $filename, string $description): bool
    {
        return array_key_exists($filename . self::SEPARATOR . $description, self::$withs);
    }
    /**
     * @return array<int|string, mixed>
     *
     * @throws ShouldNotHappen
     */
    public static function get(string $filename, string $description): array
    {
        $dataset = self::$withs[$filename . self::SEPARATOR . $description];
        $dataset = self::resolve($dataset, $filename);
        if ($dataset === null) {
            throw Should_Not_Happen::from_message('Dataset [%s] not resolvable.');
        }
        return $dataset;
    }
    /**
     * Resolves the current dataset to an array value.
     *
     * @param  array<Closure|iterable<int|string, mixed>|string>  $dataset
     * @return array<string, mixed>|null
     */
    public static function resolve(array $dataset, string $current_test_file): ?array
    {
        if ($dataset === []) {
            return null;
        }
        $dataset = self::process_datasets($dataset, $current_test_file);
        $dataset_combinations = self::get_datasets_combinations($dataset);
        $dataset_descriptions = [];
        $dataset_values = [];
        foreach ($dataset_combinations as $dataset_combination) {
            $partial_descriptions = [];
            $values = [];
            foreach ($dataset_combination as $dataset_combination_element) {
                $partial_descriptions[] = $dataset_combination_element['label'];
                $values = array_merge($values, $dataset_combination_element['values']);
            }
            $dataset_descriptions[] = implode(' / ', $partial_descriptions);
            $dataset_values[] = $values;
        }
        foreach (array_count_values($dataset_descriptions) as $description_to_check => $count) {
            if ($count > 1) {
                $index = 1;
                foreach ($dataset_descriptions as $i => $dataset_description) {
                    if ($dataset_description === $description_to_check) {
                        $dataset_descriptions[$i] .= sprintf(' #%d', $index++);
                    }
                }
            }
        }
        $named_data = [];
        foreach ($dataset_descriptions as $i => $dataset_description) {
            $named_data[$dataset_description] = $dataset_values[$i];
        }
        return $named_data;
    }
    /**
     * @param  array<Closure|iterable<int|string, mixed>|string>  $datasets
     * @return array<int, array<int, mixed>>
     */
    private static function process_datasets(array $datasets, string $current_test_file): array
    {
        $processed_datasets = [];
        foreach ($datasets as $index => $data) {
            $processed_dataset = [];
            if (is_string($data)) {
                $datasets[$index] = self::get_scoped_dataset($data, $current_test_file);
            }
            if (is_callable($datasets[$index])) {
                $datasets[$index] = call_user_func($datasets[$index]);
            }
            if ($datasets[$index] instanceof Traversable) {
                $preserve_keys_for_array_iterator = $datasets[$index] instanceof Generator && is_string($datasets[$index]->key());
                $datasets[$index] = iterator_to_array($datasets[$index], $preserve_keys_for_array_iterator);
            }
            foreach ($datasets[$index] as $key => $values) {
                $values = is_array($values) ? $values : [$values];
                $processed_dataset[] = ['label' => self::get_dataset_description($key, $values), 'values' => $values];
            }
            $processed_datasets[] = $processed_dataset;
        }
        return $processed_datasets;
    }
    /**
     * @return Closure|iterable<int|string, mixed>
     */
    private static function get_scoped_dataset(string $name, string $current_test_file): Closure|iterable
    {
        $matching_datasets = array_filter(self::$datasets, function (string $key) use ($name, $current_test_file): bool {
            [$dataset_scope, $dataset_name] = explode(self::SEPARATOR, $key);
            if ($name !== $dataset_name) {
                return false;
            }
            return str_starts_with($current_test_file, $dataset_scope);
        }, ARRAY_FILTER_USE_KEY);
        /** @var string|null $closestScopeDatasetKey */
        $closest_scope_dataset_key = array_reduce(array_keys($matching_datasets), fn(string|int|null $key_a, string|int|null $key_b): string|int|null => $key_a !== null && strlen((string) $key_a) > strlen((string) $key_b) ? $key_a : $key_b);
        if ($closest_scope_dataset_key === null) {
            throw new Dataset_Does_Not_Exist($name);
        }
        return $matching_datasets[$closest_scope_dataset_key];
    }
    /**
     * @param  array<array<mixed>>  $combinations
     * @return array<array<array<mixed>>>
     */
    private static function get_datasets_combinations(array $combinations): array
    {
        $result = [[]];
        foreach ($combinations as $index => $values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($values as $value) {
                    $tmp[] = array_merge($result_item, [$index => $value]);
                }
            }
            $result = $tmp;
        }
        return $result;
    }
    /**
     * @param  array<int, mixed>  $data
     */
    private static function get_dataset_description(int|string $key, array $data): string
    {
        $exporter = Exporter::default();
        if (is_int($key)) {
            return sprintf('(%s)', $exporter->shortened_recursive_export($data));
        }
        return sprintf('dataset "%s"', $key);
    }
}