<?php

declare (strict_types=1);
namespace Pest\Console;

use Pest\Bootstrappers\Boot_View;
use Pest\Support\View;
use Symfony\Component\Console\Helper\Symfony_Question_Helper;
use Symfony\Component\Console\Input\Array_Input;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
use Symfony\Component\Console\Question\Confirmation_Question;
/**
 * @internal
 */
final readonly class Thanks
{
    /**
     * The support options.
     *
     * @var array<string, string>
     */
    private const array FUNDING_MESSAGES = ['Star' => 'https://github.com/pestphp/pest', 'YouTube' => 'https://youtube.com/@nunomaduro', 'TikTok' => 'https://tiktok.com/@enunomaduro', 'Twitch' => 'https://twitch.tv/nunomaduro', 'LinkedIn' => 'https://linkedin.com/in/nunomaduro', 'Instagram' => 'https://instagram.com/enunomaduro', 'X' => 'https://x.com/enunomaduro', 'Sponsor' => 'https://github.com/sponsors/nunomaduro'];
    /**
     * Creates a new Console Command instance.
     */
    public function __construct(private Input_Interface $input, private Output_Interface $output)
    {
        // ..
    }
    /**
     * Executes the Console Command.
     */
    public function __invoke(): void
    {
        $bootstrapper = new Boot_View($this->output);
        $bootstrapper->boot();
        $wants_to_support = false;
        if (getenv('PEST_NO_SUPPORT') !== 'true' && $this->input->is_interactive()) {
            $wants_to_support = (new Symfony_Question_Helper())->ask(new Array_Input([]), $this->output, new Confirmation_Question(' <options=bold>Wanna show Pest some love by starring it on GitHub?</>', false));
            View::render('components.new-line');
            foreach (self::FUNDING_MESSAGES as $message => $link) {
                View::render('components.two-column-detail', ['left' => $message, 'right' => $link]);
            }
            View::render('components.new-line');
        }
        if ($wants_to_support === true) {
            if (PHP_OS_FAMILY === 'Darwin') {
                exec('open https://github.com/pestphp/pest');
            }
            if (PHP_OS_FAMILY === 'Windows') {
                exec('start https://github.com/pestphp/pest');
            }
            if (PHP_OS_FAMILY === 'Linux') {
                exec('xdg-open https://github.com/pestphp/pest');
            }
        }
    }
}