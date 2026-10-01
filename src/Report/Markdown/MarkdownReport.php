<?php

declare(strict_types=1);

namespace BafS\PsalmTypecov\Report\Markdown;

use BafS\PsalmTypecov\Report\ReportInterface;

final class MarkdownReport implements ReportInterface
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private string $outputFile,
    ) {
    }

    #[\Override]
    public function generate(iterable $result): void
    {
        $rows = '';
        $mixed_count = 0;
        $nonmixed_count = 0;

        foreach ($result as $file_path => [$path_mixed_count, $path_nonmixed_count]) {
            $mixed_count += $path_mixed_count;
            $nonmixed_count += $path_nonmixed_count;

            $rows .= sprintf(
                "| %s | %s%% | %d | %d |\n",
                str_replace('|', '\|', $file_path),
                self::formatPercentage($path_mixed_count, $path_nonmixed_count),
                $path_mixed_count,
                $path_nonmixed_count,
            );
        }

        $markdown = "# Type coverage\n\n";

        if ($mixed_count + $nonmixed_count > 0) {
            $markdown .= sprintf(
                "Total: **%s%%** (%d mixed, %d non-mixed)\n\n",
                self::formatPercentage($mixed_count, $nonmixed_count),
                $mixed_count,
                $nonmixed_count,
            );
        }

        $markdown .= "| File | Coverage | Mixed | Non-mixed |\n"
            . "| --- | ---: | ---: | ---: |\n"
            . $rows;

        if (file_put_contents($this->outputFile, $markdown) === false) {
            throw new \RuntimeException('Report could not be written to ' . $this->outputFile);
        }
    }

    /**
     * @psalm-pure
     */
    private static function formatPercentage(int $mixed_count, int $nonmixed_count): string
    {
        return number_format(100 * $nonmixed_count / ($mixed_count + $nonmixed_count), 3);
    }
}
