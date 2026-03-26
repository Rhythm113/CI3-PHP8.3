<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies that all required framework directories and entry-point files
 * are present in the repository.
 */
class FrameworkStructureTest extends TestCase
{
    /** @var string Absolute path to the project root */
    private string $root;

    protected function setUp(): void
    {
        $this->root = realpath(__DIR__ . '/..');
    }

    // ---------------------------------------------------------------
    // Directory existence
    // ---------------------------------------------------------------

    /**
     * @dataProvider requiredDirectoriesProvider
     */
    #[DataProvider('requiredDirectoriesProvider')]
    public function testRequiredDirectoryExists(string $dir): void
    {
        $this->assertDirectoryExists(
            $this->root . DIRECTORY_SEPARATOR . $dir,
            "Required directory '{$dir}' is missing."
        );
    }

    public static function requiredDirectoriesProvider(): array
    {
        return [
            ['system'],
            ['system/core'],
            ['system/database'],
            ['system/libraries'],
            ['system/helpers'],
            ['application'],
            ['application/config'],
            ['application/controllers'],
            ['application/core'],
            ['application/libraries'],
            ['application/models'],
            ['application/views'],
            ['vendor'],
        ];
    }

    // ---------------------------------------------------------------
    // File existence
    // ---------------------------------------------------------------

    /**
     * @dataProvider requiredFilesProvider
     */
    #[DataProvider('requiredFilesProvider')]
    public function testRequiredFileExists(string $file): void
    {
        $this->assertFileExists(
            $this->root . DIRECTORY_SEPARATOR . $file,
            "Required file '{$file}' is missing."
        );
    }

    public static function requiredFilesProvider(): array
    {
        return [
            ['index.php'],
            ['composer.json'],
            ['.htaccess'],
            ['system/core/CodeIgniter.php'],
            ['system/core/Controller.php'],
            ['system/core/Model.php'],
            ['system/core/Router.php'],
            ['system/core/Security.php'],
            ['system/core/Input.php'],
            ['system/core/Output.php'],
            ['system/core/Config.php'],
            ['application/config/config.php'],
            ['application/config/routes.php'],
            ['application/config/database.php'],
            ['application/config/jwt.php'],
            ['application/controllers/Api.php'],
            ['application/controllers/Welcome.php'],
            ['application/core/API_Controller.php'],
            ['application/libraries/Jwt_lib.php'],
            ['application/libraries/Rate_limiter.php'],
            ['application/libraries/Redis_lib.php'],
            ['application/libraries/Curl_lib.php'],
        ];
    }

    // ---------------------------------------------------------------
    // Composer manifest
    // ---------------------------------------------------------------

    public function testComposerJsonIsValid(): void
    {
        $path = $this->root . DIRECTORY_SEPARATOR . 'composer.json';
        $this->assertFileExists($path);

        $json = json_decode(file_get_contents($path), true);
        $this->assertIsArray($json, 'composer.json must be valid JSON.');
        $this->assertArrayHasKey('require', $json);
        $this->assertArrayHasKey('php', $json['require']);
    }

    public function testVendorAutoloadExists(): void
    {
        $this->assertFileExists(
            $this->root . '/vendor/autoload.php',
            'vendor/autoload.php is missing - run "composer install".'
        );
    }
}
