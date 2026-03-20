<?php

declare (strict_types=1);
namespace Pest;

use Nuno_Maduro\Collision\Writer;
use Pest\Contracts\Bootstrapper;
use Pest\Exceptions\Fatal_Exception;
use Pest\Exceptions\No_Dirty_Tests_Found;
use Pest\Plugins\Actions\Calls_Adds_Output;
use Pest\Plugins\Actions\Calls_Boot;
use Pest\Plugins\Actions\Calls_Handle_Arguments;
use Pest\Plugins\Actions\Calls_Handle_Original_Arguments;
use Pest\Plugins\Actions\Calls_Terminable;
use Pest\Support\Container;
use Pest\Support\Reflection;
use Pest\Support\View;
use Php_Unit\Test_Runner\Test_Result\Facade;
use Php_Unit\Text_Ui\Application;
use Php_Unit\Text_Ui\Configuration\Registry;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
use Throwable;
use Whoops\Exception\Inspector;
/**
 * @internal
 */
final readonly class Kernel
{
    /**
     * The Kernel bootstrappers.
     *
     * @var array<int, class-string>
     */
    private const array BOOTSTRAPPERS = [Bootstrappers\Boot_Overrides::class, Bootstrappers\Boot_Subscribers::class, Bootstrappers\Boot_Files::class, Bootstrappers\Boot_View::class, Bootstrappers\Boot_Kernel_Dump::class, Bootstrappers\Boot_Exclude_List::class];
    /**
     * Creates a new Kernel instance.
     */
    public function __construct(private Application $application, private Output_Interface $output)
    {
    }
    /**
     * Boots the Kernel.
     */
    public static function boot(Test_Suite $test_suite, Input_Interface $input, Output_Interface $output): self
    {
        $container = Container::get_instance();
        $container->add(Test_Suite::class, $test_suite)->add(Input_Interface::class, $input)->add(Output_Interface::class, $output)->add(Container::class, $container);
        $kernel = new self(new Application(), $output);
        register_shutdown_function($kernel->shutdown(...));
        foreach (self::BOOTSTRAPPERS as $bootstrapper) {
            $bootstrapper = Container::get_instance()->get($bootstrapper);
            assert($bootstrapper instanceof Bootstrapper);
            $bootstrapper->boot();
        }
        Calls_Boot::execute();
        Container::get_instance()->add(self::class, $kernel);
        return $kernel;
    }
    /**
     * Runs the application, and returns the exit code.
     *
     * @param  array<int, string>  $originalArguments
     * @param  array<int, string>  $arguments
     */
    public function handle(array $original_arguments, array $arguments): int
    {
        Calls_Handle_Original_Arguments::execute($original_arguments);
        $arguments = Calls_Handle_Arguments::execute($arguments);
        try {
            $this->application->run($arguments);
        } catch (No_Dirty_Tests_Found) {
            $this->output->writeln(['', '  <fg=white;options=bold;bg=blue> INFO </> No tests found.', '']);
        }
        $configuration = Registry::get();
        $result = Facade::result();
        return Calls_Adds_Output::execute(Result::exit_code($configuration, $result));
    }
    /**
     * Terminate the Kernel.
     */
    public function terminate(): void
    {
        $pre_buffer_output = Container::get_instance()->get(Kernel_Dump::class);
        assert($pre_buffer_output instanceof Kernel_Dump);
        $pre_buffer_output->terminate();
        Calls_Terminable::execute();
    }
    /**
     * Shutdowns unexpectedly the Kernel.
     */
    public function shutdown(): void
    {
        $this->terminate();
        if (is_array($error = error_get_last())) {
            if (!in_array($error['type'], [E_ERROR, E_CORE_ERROR], true)) {
                return;
            }
            $message = $error['message'];
            $file = $error['file'];
            $line = $error['line'];
            try {
                $writer = new Writer(null, $this->output);
                $throwable = new Fatal_Exception($message);
                Reflection::set_property_value($throwable, 'line', $line);
                Reflection::set_property_value($throwable, 'file', $file);
                $inspector = new Inspector($throwable);
                $writer->write($inspector);
            } catch (Throwable) {
                // @phpstan-ignore-line
                View::render('components.badge', ['type' => 'ERROR', 'content' => sprintf('%s in %s:%d', $message, $file, $line)]);
            }
            exit(1);
        }
    }
}