<?php

namespace JsonAst\Helper;

class CodeFragment
{
    public static function getFragment($code, $linePos, $columnPos, $settings)
    {
        $settings = array_merge_recursive(
            [
            'extraLines' => 2,
            'tabSize' => 4
            ],
            $settings
        );
        $lines = preg_split('/\r\n?|\n|\f/', $code);
        $startLinePos = max(1, $linePos - $settings['extraLines']) - 1;
        $endLinePos = min($linePos + $settings['extraLines'], count($lines));
        $maxNumLength = strlen($endLinePos);
        $previousLines = self::printLines($lines, $startLinePos, $linePos, $maxNumLength, $settings);
        $targetLineBeforeCursor = self::printLine(
            substr(
                $lines[$linePos - 1],
                0,
                $columnPos - 1
            ),
            $linePos,
            $maxNumLength,
            $settings
        );

        $cursorLine = str_repeat(' ', strlen($targetLineBeforeCursor)) . '^';
        $nextLines = self::printLines($lines, $linePos, $endLinePos, $maxNumLength, $settings);

        return implode(
            "\n",
            array_filter(
                [$previousLines, $cursorLine, $nextLines],
                function ($value) {
                    return $value ?? false;
                }
            )
        );
    }

    private static function printLines($lines, $start, $end, $maxNumLength, $settings)
    {
        $code = array_slice($lines, $start, $end);
        array_walk(
            $code,
            function (&$line, $index, $data) {
                $line = self::printLine($line, $data['start'] + $index + 1, $data['max'], $data['settings']);
            },
            ['start' => $start, 'end' => $end, 'max' => $maxNumLength, 'settings' => $settings]
        );

        return implode("\n", $code);
    }

    private static function printLine($line, $position, $maxNumLength, $settings)
    {
        $formattedNum = str_pad($position, $maxNumLength, ' ');
        $tabReplacement = str_repeat(' ', $settings['tabSize']);

        return $formattedNum . ' | ' . preg_replace('/\t/', $tabReplacement, $line);
    }
}
