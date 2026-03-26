<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies that every PHP source file in the project parses without errors.
 *
 * The test shells out to `php -l` for each file so that syntax errors are
 * caught early, before the framework is even bootstrapped.
 */
class PhpSyntaxTest extends TestCase
{
    /**
     * Returns an iterator of all PHP files under the given root directories.
     *
     * @return array<array{string}>
     */
    public static function phpFileProvider(): array
    {
        $root  = realpath(__DIR__ . '/..');
        $dirs  = ['application', 'system'];
        $files = [];

        foreach ($dirs as $dir)
        {
            $path = $root . DIRECTORY_SEPARATOR . $dir;
            if ( ! is_dir($path))
            {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file)
            {
                if ($file->getExtension() === 'php')
                {
                    // Skip cache files that may contain non-parseable data
                    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    if (strpos($relative, 'application' . DIRECTORY_SEPARATOR . 'cache') === 0)
                    {
                        continue;
                    }
                    $files[] = [$file->getPathname()];
                }
            }
        }

        return $files;
    }

    /**
     * @dataProvider phpFileProvider
     */
    #[DataProvider('phpFileProvider')]
    public function testPhpFileParsesWithoutErrors(string $path): void
    {
        $output     = [];
        $returnCode = 0;
        $escaped    = escapeshellarg($path);

        exec("php -l {$escaped} 2>&1", $output, $returnCode);

        $this->assertSame(
            0,
            $returnCode,
            "PHP syntax error in {$path}:\n" . implode("\n", $output)
        );
    }
}
