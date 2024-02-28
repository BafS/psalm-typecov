<?php

declare(strict_types=1);

namespace BafS\PsalmTypecov\Report\Html;

use BafS\PsalmTypecov\Report\ReportInterface;
use BafS\PsalmTypecov\Report\Thresholds;

final class HtmlReport implements ReportInterface
{
    public function __construct(
        private Thresholds $thresholds,
        private string $outputFile,
    ) {
    }

    public function generate(iterable $result): void
    {
        $html = $this->render(__DIR__ . '/template/index.phtml', [
            'thresholds' => $this->thresholds,
            'result' => $result,
            'coverageClass' => function (int $nonmixed, int $total): string {
                $percentage = 100 * $nonmixed / $total;

                if ($total <= 0) {
                    return 'coverage-na';
                }

                if ($percentage > $this->thresholds->highLowerBound) {
                    return 'coverage-high';
                }

                if ($percentage > $this->thresholds->lowUpperBound) {
                    return 'coverage-medium';
                }

                return 'coverage-low';
            },
        ]);

        if ($html === false || $html === '') {
            throw new \RuntimeException('Report could not be generated');
        }

        file_put_contents($this->outputFile, $html);
    }

    private function render(string $file, array $data): string|false
    {
        ob_start();
        extract($data, EXTR_SKIP);
        /** @psalm-suppress MissingFile, UnresolvableInclude */
        require $file;

        return ob_get_clean();
    }
}
