<?php
use PHPUnit\Framework\TestCase;
use Reductionist\Reductionist;

class ReductionistTest extends TestCase 
{
    protected $modx;
    protected $reductionist;
    protected $basePath;
    protected $srcDir = '/assets/';
    protected $outDir = '/output/';
    protected $removeOutput = true;
    protected $width = 400;
    protected $height = 300;
    protected $quality = 60;
    protected $formats = ['jpg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'gif' => IMAGETYPE_GIF];
    protected $newFormats = ['webp' => IMAGETYPE_WEBP, 'jp2' => IMAGETYPE_JP2];
    protected $testNewFormats = true;
    protected $graphicsLib = 2;
    
    protected function setUp(): void
    {
        $this->basePath = dirname(dirname(__FILE__));
        require_once(dirname($this->basePath) . '/vendor/autoload.php');
        require_once(dirname($this->basePath) . '/src/Reductionist/Reductionist.php');
        $this->reductionist = new Reductionist($this->graphicsLib);
        if (!is_dir($this->basePath . $this->outDir)) mkdir($this->basePath . $this->outDir);
    }

    public function testConfig()
    {
        $this->assertTrue($this->reductionist instanceof Reductionist);
    }

    public function testResizeWidth() 
    {
        $dir = $this->basePath . $this->srcDir;
        $files = scandir($dir);
        foreach ($files as $file) {
            if (strpos($file, '.') === 0) continue;
            if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) continue;
            $full = $this->basePath . $this->srcDir . $file;
            $thumb = $this->basePath . $this->outDir . $file;
            $this->reductionist->processImage(
                $full,
                $thumb,
                ['w' => $this->width, 'q' => $this->quality]
            );
            $this->assertFileExists($thumb);
            $this->assertFileNotEquals($full, $thumb);
            $this->assertGreaterThan(filesize($thumb), filesize($full));
            $this->assertEquals(getimagesize($thumb)[0], $this->width);
            if ($this->removeOutput) unlink($thumb);
        }
    }

    public function testResizeHeight() 
    {
        $dir = $this->basePath . $this->srcDir;
        $files = scandir($dir);
        foreach ($files as $file) {
            if (strpos($file, '.') === 0) continue;
            if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) continue;
            $full = $this->basePath . $this->srcDir . $file;
            $thumb = $this->basePath . $this->outDir . $file;
            $this->reductionist->processImage(
                $full,
                $thumb,
                ['h' => $this->height, 'q' => $this->quality]
            );
            $this->assertFileExists($thumb);
            $this->assertFileNotEquals($full, $thumb);
            $this->assertGreaterThan(filesize($thumb), filesize($full));
            $this->assertEquals(getimagesize($thumb)[1], $this->height);
            if ($this->removeOutput) unlink($thumb);
        }
    }

    public function testConvertFormat()
    {
        $this->removeOutput = false;
        $dir = $this->basePath . $this->srcDir;
        $files = scandir($dir);
        $failures = [];
        foreach ($files as $file) {
            if (strpos($file, '.') === 0) continue;
            if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) continue;
            $full = $this->basePath . $this->srcDir . $file; 
            $formats = ($this->testNewFormats) ? array_merge($this->formats, $this->newFormats) : $this->formats;
            foreach ($formats as $ext => $type) {
                if (pathinfo($full, PATHINFO_EXTENSION) === $ext) continue;
                $thumb = $this->basePath . $this->outDir . $file . '.' . $ext;
                $this->reductionist->processImage(
                    $full,
                    $thumb,
                    ['h' => $this->height, 'q' => $this->quality]
                );
                try {
                    $this->assertFileExists($thumb);
                    $this->assertFileNotEquals($full, $thumb);
                    $this->assertGreaterThan(filesize($thumb), filesize($full));
                    $this->assertEquals(getimagesize($thumb)[1], $this->height);
                    $this->assertEquals($type, getimagesize($thumb)[2]);
                } catch(PHPUnit_Framework_ExpectationFailedException $e) {
                    $failures[] = $e->getMessage();
                }
                if ($this->removeOutput) unlink($thumb);
            }
        }
        if(!empty($failures)) {
            throw new PHPUnit_Framework_ExpectationFailedException (count($failures) . " assertions failed:\n\t" . implode("\n\t", $failures));
        }
    }
}