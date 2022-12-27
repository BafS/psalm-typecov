<?php

declare(strict_types=1);

namespace BafS\PsalmTypecov;

use BafS\PsalmTypecov\Report\Html\HtmlReport;
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
    private static array $options = [];

    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        if (isset($config->htmlReport)) {
            self::$options['htmlReport'] = $this->extractOptionsFromElement($config->htmlReport);
        }

        $registration->registerHooksFromClass(self::class);
    }

    /**
     * Called after analysis is complete
     */
    public static function afterAnalysis(
        AfterAnalysisEvent $event
    ): void {
        $codebase = $event->getCodebase();

        self::createReporter()->generate(self::getNonMixedStats($codebase));
    }

    private static function createReporter(): ReportInterface
    {
        if (isset(self::$options['htmlReport'])) {
            if (!isset(self::$options['htmlReport']['output'])) {
                throw new \RuntimeException('"output" attribute must be set in htmlReport');
            }

            return new HtmlReport(
                Thresholds::from(50, 90),
                self::$options['htmlReport']['output'],
            );
        }

        throw new \RuntimeException('No report set in the configuration');
    }

    /**
     * @psalm-suppress InternalMethod
     * @psalm-return iterable<string, array{int, int}> mixed vs non-mixed variables
     */
    private static function getNonMixedStats(Codebase $codebase): iterable
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
