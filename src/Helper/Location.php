<?php

namespace JsonAst\Helper;

class Location
{
    public static function loc(
        $startLine,
        $startColumn,
        $startOffset,
        $endLine,
        $endColumn,
        $endOffset,
        $source
    ) {
        return [
            'start' => [
                'line' => $startLine,
                'column' => $startColumn,
                'offset' => $startOffset
            ],
            'end' => [
                'line' => $endLine,
                'column' => $endColumn,
                'offset' => $endOffset
            ],
            'source' => $source ?? null
        ];
    }
}
