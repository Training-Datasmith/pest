<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Composer\Installed_Versions;
use Pest\Console\Thanks;
use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Support\View;
use Pest\Test_Suite;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final readonly class Init implements Handles_Arguments
{
    /**
     * The option the triggers the init job.
     */
    private const string INIT_OPTION = '--init';
    /**
     * The files that will be created.
     */
    private const array STUBS = ['phpunit.xml.stub' => 'phpunit.xml', 'Pest.php.stub' => 'tests/Pest.php', 'TestCase.php.stub' => 'tests/TestCase.php', 'Unit/ExampleTest.php.stub' => 'tests/Unit/ExampleTest.php', 'Feature/ExampleTest.php.stub' => 'tests/Feature/ExampleTest.php'];
    /**
     * Creates a new Plugin instance.
     */
    public function __construct(private Test_Suite $test_suite, private Input_Interface $input, private Output_Interface $output)
    {
        // ..
    }
    /**
     * {@inheritdoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if (!array_key_exists(1, $arguments)) {
            return $arguments;
        }
        if ($arguments[1] !== self::INIT_OPTION) {
            return $arguments;
        }
        unset($arguments[1]);
        $this->init();
        exit(0);
    }
    /**
     * Initializes the tests directory.
     */
    public function init(): void
    {
        $tests_base_dir = "{$this->test_suite->root_path}/tests";
        if (!is_dir($tests_base_dir)) {
            mkdir($tests_base_dir);
        }
        View::render('components.badge', ['type' => 'INFO', 'content' => 'Preparing tests directory.']);
        foreach (self::STUBS as $from => $to) {
            if ($this->is_laravel_installed()) {
                $from_path = __DIR__ . "/../../stubs/init-laravel/{$from}";
            } else {
                $from_path = __DIR__ . "/../../stubs/init/{$from}";
            }
            $to_path = "{$this->test_suite->root_path}/{$to}";
            if (file_exists($to_path)) {
                View::render('components.two-column-detail', ['left' => $to, 'right' => 'File already exists.']);
                continue;
            }
            if (!is_dir(dirname($to_path))) {
                mkdir(dirname($to_path));
            }
            copy($from_path, $to_path);
            View::render('components.two-column-detail', ['left' => $to, 'right' => 'File created.']);
        }
        View::render('components.new-line');
        (new Thanks($this->input, $this->output))();
    }
    /**
     * Checks if laravel is installed through Composer
     */
    private function is_laravel_installed(): bool
    {
        return Installed_Versions::is_installed('laravel/framework');
    }
}