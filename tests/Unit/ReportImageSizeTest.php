<?php

namespace Tests\Unit;

use App\Markdown\ReportImageSizeExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReportImageSizeTest extends TestCase
{
    private function render(string $markdown): string
    {
        $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new ReportImageSizeExtension);

        return (string) (new MarkdownConverter($environment))->convert($markdown);
    }

    #[Test]
    public function image_width_is_rendered_without_changing_the_source_or_title(): void
    {
        $html = $this->render('![Radar](https://example.test/radar.png#report-image-width=400 "Session screenshot")');

        $this->assertStringContainsString('width="400"', $html);
        $this->assertStringContainsString('src="https://example.test/radar.png"', $html);
        $this->assertStringContainsString('title="Session screenshot"', $html);
        $this->assertStringNotContainsString('#report-image-width', $html);
    }

    #[Test]
    public function ordinary_images_and_links_are_unchanged(): void
    {
        $html = $this->render('![Radar](https://example.test/radar.png "Radar") [Link](https://example.test/#report-image-width=400)');

        $this->assertStringNotContainsString('width="', $html);
        $this->assertStringContainsString('title="Radar"', $html);
        $this->assertStringContainsString('href="https://example.test/#report-image-width=400"', $html);
    }

    #[Test]
    #[DataProvider('invalidWidths')]
    public function invalid_widths_do_not_become_html_attributes(string $width): void
    {
        $html = $this->render('![Radar](https://example.test/radar.png#report-image-width=' . $width . ')');

        $this->assertStringNotContainsString('width="', $html);
    }

    public static function invalidWidths(): array
    {
        return [['0'], ['-1'], ['1.5'], ['100000'], ['400px'], ['400;onerror=alert(1)']];
    }

    #[Test]
    public function raw_html_remains_stripped(): void
    {
        $html = $this->render('<img src="radar.png" onerror="alert(1)">');

        $this->assertStringNotContainsString('<img', $html);
    }
}
