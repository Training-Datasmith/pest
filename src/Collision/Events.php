<?php

declare (strict_types=1);
namespace Pest\Collision;

use Nuno_Maduro\Collision\Adapters\Phpunit\Test_Result;
use Pest\Configuration\Project;
use Symfony\Component\Console\Output\Output_Interface;
use function Termwind\render;
use function Termwind\Render_Using;
/**
 * @internal
 */
final class Events
{
    /**
     * Sets the output.
     */
    private static ?Output_Interface $output = null;
    /**
     * Sets the output.
     */
    public static function set_output(Output_Interface $output): void
    {
        self::$output = $output;
    }
    /**
     * Fires before the test method description is printed.
     */
    public static function before_test_method_description(Test_Result $result, string $description): string
    {
        if (($context = $result->context) === []) {
            return $description;
        }
        render_using(self::$output);
        ['assignees' => $assignees, 'issues' => $issues, 'prs' => $prs] = $context;
        if (($link = Project::get_instance()->issues) !== '') {
            $issues_description = array_map(fn(int $issue): string => sprintf('<a href="%s">#%s</a>', sprintf($link, $issue), $issue), $issues);
        }
        if (($link = Project::get_instance()->prs) !== '') {
            $prs_description = array_map(fn(int $pr): string => sprintf('<a href="%s">#%s</a>', sprintf($link, $pr), $pr), $prs);
        }
        if (($link = Project::get_instance()->assignees) !== '' && count($assignees) > 0) {
            $assignees_description = array_map(fn(string $assignee): string => sprintf('<a href="%s">@%s</a>', sprintf($link, $assignee), $assignee), $assignees);
        }
        if (count($assignees) > 0 || count($issues) > 0 || count($prs) > 0) {
            $description .= ' ' . implode(', ', array_merge($issues_description ?? [], $prs_description ?? [], isset($assignees_description) ? ['[' . implode(', ', $assignees_description) . ']'] : []));
        }
        return $description;
    }
    /**
     * Fires after the test method description is printed.
     */
    public static function after_test_method_description(Test_Result $result): void
    {
        if (($context = $result->context) === []) {
            return;
        }
        render_using(self::$output);
        ['notes' => $notes] = $context;
        foreach ($notes as $note) {
            render(sprintf(<<<'HTML'
            <div class="ml-2">
                <span class="text-gray"> // %s</span>
            </div>
            HTML, $note));
        }
    }
}