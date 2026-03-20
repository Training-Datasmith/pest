<?php

declare (strict_types=1);
namespace Pest;

use Nuno_Maduro\Collision\Writer;
use Pest\Exceptions\Test_Description_Missing;
use Pest\Support\Container;
use Symfony\Component\Console\Output\Console_Output;
use Symfony\Component\Console\Output\Output_Interface;
use Throwable;
use Whoops\Exception\Inspector;
final readonly class Panic
{
    /**
     * Creates a new Panic instance.
     */
    private function __construct(private Throwable $throwable)
    {
        // ...
    }
    /**
     * Creates a new Panic instance, and exits the application.
     */
    public static function with(Throwable $throwable): never
    {
        if ($throwable instanceof Test_Description_Missing && !is_null($previous = $throwable->get_previous())) {
            $throwable = $previous;
        }
        $panic = new self($throwable);
        $panic->handle();
        exit(1);
    }
    /**
     * Handles the panic.
     */
    private function handle(): void
    {
        try {
            $output = Container::get_instance()->get(Output_Interface::class);
        } catch (Throwable) {
            $output = new Console_Output();
        }
        assert($output instanceof Output_Interface);
        if ($this->throwable instanceof Contracts\Panicable) {
            $this->throwable->render($output);
            exit($this->throwable->exit_code());
        }
        $writer = new Writer(null, $output);
        $inspector = new Inspector($this->throwable);
        $writer->write($inspector);
        $output->writeln('');
        exit(1);
    }
}