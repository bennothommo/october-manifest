<?php
namespace October\Manifest;

/**
 * Simple class to output text and errors to the CLI.
 *
 * @author Ben Thomson
 */
class Cli
{
    /**
     * Contains the last output through the `out()` method.
     *
     * This is mainly so `overwrite()` can append to the last message.
     *
     * @var string
     */
    protected static $lastOutput = '';

    /**
     * Outputs text to the CLI.
     *
     * @param string $text Text to output.
     * @param integer $lines Line breaks after the text. Must be at least 1.
     * @return void
     */
    public static function out(string $text = '', int $lines = 1)
    {
        if ($lines < 1) {
            $lines = 1;
        }

        fwrite(STDOUT, $text . str_repeat("\n", $lines));
        self::$lastOutput = $text;
    }

    /**
     * Outputs text to the CLI over the last printed line.
     *
     * @param string $text
     * @return void
     */
    public static function overwrite(string $text, bool $includeLastOutput = true, int $lines = 1)
    {
        if ($lines < 1) {
            $lines = 1;
        }

        fwrite(STDOUT, "\e[1A");
        self::out((($includeLastOutput) ? self::$lastOutput . ' ' : '' ) . $text, $lines);
    }

    /**
     * Outputs a message to the CLI and exits the program in a successful state.
     *
     * @param string $text Success message to show.
     * @return void
     */
    public static function finish(string $text)
    {
        fwrite(STDOUT, $text . "\n");
        exit(0);
    }

    /**
     * Outputs an error to the CLI error stream and exits the program in a failed state.
     *
     * @param string $text Error message to show.
     * @return void
     */
    public static function error(string $text)
    {
        fwrite(STDERR, $text . "\n");
        exit(1);
    }
}
