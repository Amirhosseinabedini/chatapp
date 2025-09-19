<?php

namespace App\Tests\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    private AppExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new AppExtension();
    }

    public function testFormatFileSizeFilterExists(): void
    {
        $filters = $this->extension->getFilters();
        
        $this->assertCount(1, $filters);
        $this->assertEquals('formatFileSize', $filters[0]->getName());
    }

    public function testFormatFileSizeZeroBytes(): void
    {
        $result = $this->extension->formatFileSize(0);
        $this->assertEquals('0 Bytes', $result);
    }

    public function testFormatFileSizeBytes(): void
    {
        $result = $this->extension->formatFileSize(500);
        $this->assertEquals('500 Bytes', $result);
    }

    public function testFormatFileSizeKB(): void
    {
        $result = $this->extension->formatFileSize(1024);
        $this->assertEquals('1 KB', $result);
        
        $result = $this->extension->formatFileSize(1536); // 1.5 KB
        $this->assertEquals('1.5 KB', $result);
    }

    public function testFormatFileSizeMB(): void
    {
        $result = $this->extension->formatFileSize(1048576); // 1 MB
        $this->assertEquals('1 MB', $result);
        
        $result = $this->extension->formatFileSize(1572864); // 1.5 MB
        $this->assertEquals('1.5 MB', $result);
    }

    public function testFormatFileSizeGB(): void
    {
        $result = $this->extension->formatFileSize(1073741824); // 1 GB
        $this->assertEquals('1 GB', $result);
        
        $result = $this->extension->formatFileSize(1610612736); // 1.5 GB
        $this->assertEquals('1.5 GB', $result);
    }

    public function testFormatFileSizeTB(): void
    {
        $result = $this->extension->formatFileSize(1099511627776); // 1 TB
        $this->assertEquals('1 TB', $result);
    }

    public function testFormatFileSizeLargeNumber(): void
    {
        $result = $this->extension->formatFileSize(2147483648); // 2 GB
        $this->assertEquals('2 GB', $result);
    }

    public function testFormatFileSizePrecision(): void
    {
        // Test that we get proper precision (2 decimal places)
        $result = $this->extension->formatFileSize(1536); // 1.5 KB
        $this->assertEquals('1.5 KB', $result);
        
        $result = $this->extension->formatFileSize(1537); // 1.5009765625 KB
        $this->assertEquals('1.5 KB', $result);
    }
}