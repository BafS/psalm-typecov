<?php

declare(strict_types=1);

namespace BafS\PsalmTypecov;

use BafS\PsalmTypecov\Report\Html\HtmlReport;
use BafS\PsalmTypecov\Report\Markdown\MarkdownReport;
use BafS\PsalmTypecov\Report\ReportInterface;
use BafS\PsalmTypecov\Report\Thresholds;
use Psalm\Codebase;
use Psalm\Plugin\EventHandler\AfterAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class TypeCoverage implements AfterAnalysisInterface, PluginEntryPointInterface
{
    /** @var array<string, mixed> */
    private static array $options = [];

    private static ?float $minFileCoverage = null;

    #[\Override]
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        if (isset($config->htmlReport)) {
            self::$options['htmlReport'] = $this->extractOptionsFromElement($config->htmlReport);
        }

        if (isset($config->markdownReport)) {
            self::$options['markdownReport'] = $this->extractOptionsFromElement($config->markdownReport);
        }

        if (isset($config->minFileCoverage)) {
            self::$minFileCoverage = self::parseMinFileCoverage((string) $config->minFileCoverage['value']);
        }

        $registration->registerHooksFromClass(self::class);
    }

    /**
     * Called after analysis is complete
     */
    #[\Override]
    public static function afterAnalysis(AfterAnalysisEvent $event): void
    {
        $minFileCoverage = self::$minFileCoverage;
        $reporters = self::createReporters();

        if ($reporters === [] && $minFileCoverage === null) {
            throw new \RuntimeException('No report or minFileCoverage set in the configuration');
        }

        // The stats are a generator, so collect them once for all reporters
        $stats = iterator_to_array(self::getNonMixedStats($event->getCodebase()));

        foreach ($reporters as $reporter) {
            $reporter->generate($stats);
        }

        if ($minFileCoverage !== null) {
            self::checkMinFileCoverage($stats, $minFileCoverage);
        }
    }

    /**
     * @psalm-pure
     */
    private static function parseMinFileCoverage(string $value): float
    {
        if (!is_numeric($value) || $value < 0 || $value > 100) {
            throw new \RuntimeException('"value" attribute of minFileCoverage must be a number between 0 and 100');
        }

        return (float) $value;
    }

    /**
     * Lists files below the threshold on STDERR (STDOUT may carry a machine-readable Psalm report)
     * and makes Psalm exit with code 2, the same code it uses when it finds errors.
     *
     * @param array<string, array{int, int}> $stats
     */
    private static function checkMinFileCoverage(array $stats, float $minFileCoverage): void
    {
        $failures = [];
        foreach ($stats as $file_path => [$mixed_count, $nonmixed_count]) {
            $percentage = 100 * $nonmixed_count / ($mixed_count + $nonmixed_count);

            if ($percentage < $minFileCoverage) {
                $failures[] = sprintf('  %s: %.2f%% (%d mixed)', $file_path, $percentage, $mixed_count);
            }
        }

        if ($failures === []) {
            return;
        }

        fwrite(STDERR, sprintf(
            "\nType coverage is below %s%% in %d file(s):\n%s\n\n",
            $minFileCoverage,
            count($failures),
            implode("\n", $failures),
        ));

        // Psalm counts errors and prints its summary before this hook runs, and exiting right away
        // would skip writing its cache and --report files, so the exit code is overridden on shutdown.
        register_shutdown_function(static function (): void {
            exit(2);
        });
    }

    /**
     * @return list<ReportInterface>
     */
    private static function createReporters(): array
    {
        $reporters = [];

        if (isset(self::$options['htmlReport'])) {
            $reporters[] = new HtmlReport(
                Thresholds::from(50, 90),
                self::getOutputOption('htmlReport'),
            );
        }

        if (isset(self::$options['markdownReport'])) {
            $reporters[] = new MarkdownReport(self::getOutputOption('markdownReport'));
        }

        return $reporters;
    }

    private static function getOutputOption(string $report): string
    {
        if (!isset(self::$options[$report]['output'])) {
            throw new \RuntimeException('"output" attribute must be set in ' . $report);
        }

        return (string) self::$options[$report]['output'];
    }

    /**
     * @psalm-suppress InternalMethod
     * @psalm-return \Generator<string, array{int, int}, mixed, void> mixed vs non-mixed variables
     */
    private static function getNonMixedStats(Codebase $codebase): \Generator
    {
        // This logic is adapted from "Analyzer#getNonMixedStats"

        // This is quite hacky but unfortunately those properties are "private"
        /** @psalm-suppress UndefinedThisPropertyFetch */
        $files_to_analyze = (fn (): array => $this->files_to_analyze)->call($codebase->analyzer);
        /** @psalm-suppress UndefinedThisPropertyFetch */
        $mixed_counts = (fn (): array => $this->mixed_counts)->call($codebase->analyzer);

        $all_deep_scanned_files = [];
        foreach ($files_to_analyze as $file_path => $_) {
            $all_deep_scanned_files[$file_path] = true;

            if (!$codebase->config->reportTypeStatsForFile($file_path)) {
                continue;
            }

            foreach ($codebase->file_storage_provider->get($file_path)->required_file_paths as $required_file_path) {
                $all_deep_scanned_files[$required_file_path] = true;
            }
        }

        foreach ($all_deep_scanned_files as $file_path => $_) {
            if (isset($mixed_counts[$file_path])) {
                [$path_mixed_count, $path_nonmixed_count] = $mixed_counts[$file_path];

                if ($path_mixed_count + $path_nonmixed_count) {
                    yield $codebase->config->shortenFileName($file_path) => $mixed_counts[$file_path];
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function extractOptionsFromElement(SimpleXMLElement $element): array
    {
        $options = [];
        foreach ($element->attributes() ?? [] as $attribute) {
            $options[$attribute->getName()] = (string) $attribute;
        }

        return $options;
    }
}
