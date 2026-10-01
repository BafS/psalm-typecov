<?php

declare(strict_types=1);

namespace BafS\PsalmTypecov\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Runs the installed Psalm on a small fixture project with the plugin enabled.
 */
final class TypeCoverageTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        $this->workDir = sys_get_temp_dir() . '/psalm-typecov-' . bin2hex(random_bytes(4));
        mkdir($this->workDir . '/src', 0777, true);

        foreach (glob(__DIR__ . '/Fixture/src/*.php') ?: [] as $file) {
            copy($file, $this->workDir . '/src/' . basename($file));
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->workDir . '/{,src/}*', GLOB_BRACE) ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($this->workDir . '/src');
        rmdir($this->workDir);
    }

    public function testGeneratesMarkdownReport(): void
    {
        [$exitCode, $output] = $this->runPsalm('<markdownReport output="' . $this->workDir . '/report.md" />');

        self::assertSame(0, $exitCode, $output);
        self::assertFileExists($this->workDir . '/report.md');

        $report = (string) file_get_contents($this->workDir . '/report.md');

        self::assertStringContainsString('# Type coverage', $report);
        self::assertMatchesRegularExpression('/^Total: \*\*\d+\.\d{3}%\*\*/m', $report);
        self::assertMatchesRegularExpression('/^\| src\/Typed\.php \| 100\.000% \| 0 \| [1-9]\d* \|$/m', $report);
        self::assertMatchesRegularExpression('/^\| src\/Untyped\.php \| \d+\.\d{3}% \| [1-9]\d* \| \d+ \|$/m', $report);
    }

    public function testGeneratesHtmlReport(): void
    {
        [$exitCode, $output] = $this->runPsalm('<htmlReport output="' . $this->workDir . '/report.html" />');

        self::assertSame(0, $exitCode, $output);
        self::assertFileExists($this->workDir . '/report.html');

        $report = (string) file_get_contents($this->workDir . '/report.html');

        self::assertStringContainsString('src/Typed.php', $report);
        self::assertStringContainsString('src/Untyped.php', $report);
    }

    public function testGeneratesBothReports(): void
    {
        [$exitCode, $output] = $this->runPsalm(
            '<htmlReport output="' . $this->workDir . '/report.html" />'
            . '<markdownReport output="' . $this->workDir . '/report.md" />',
        );

        self::assertSame(0, $exitCode, $output);
        self::assertFileExists($this->workDir . '/report.html');
        self::assertFileExists($this->workDir . '/report.md');
    }

    public function testFailsWithoutReport(): void
    {
        [$exitCode, $output] = $this->runPsalm('');

        self::assertNotSame(0, $exitCode, $output);
        self::assertStringContainsString('No report set in the configuration', $output);
    }

    /**
     * @return array{int, string} exit code and combined STDOUT/STDERR
     */
    private function runPsalm(string $pluginOptions): array
    {
        file_put_contents($this->workDir . '/psalm.xml', <<<XML
            <?xml version="1.0"?>
            <psalm
                errorLevel="8"
                findUnusedCode="false"
                resolveFromConfigFile="true"
                xmlns="https://getpsalm.org/schema/config"
            >
                <projectFiles>
                    <directory name="src" />
                </projectFiles>
                <plugins>
                    <pluginClass class="BafS\PsalmTypecov\TypeCoverage">{$pluginOptions}</pluginClass>
                </plugins>
            </psalm>
            XML);

        $command = [
            PHP_BINARY,
            dirname(__DIR__) . '/vendor/bin/psalm',
            '--config=' . $this->workDir . '/psalm.xml',
            '--no-cache',
            '--no-progress',
            '--threads=1',
        ];

        // Run from the repository root so Psalm loads the plugin through this package's autoloader
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, dirname(__DIR__));
        self::assertIsResource($process);

        $output = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        return [proc_close($process), $output];
    }
}
