<?php

namespace App\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExtensionInterface;

class ReportImageSizeExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event): void {
            foreach ($event->getDocument()->iterator() as $node) {
                if (! $node instanceof Image || ! preg_match('/#report-image-width=([1-9][0-9]{0,4})$/', $node->getUrl(), $matches)) {
                    continue;
                }

                $node->data->set('attributes/width', $matches[1]);
                $node->setUrl(substr($node->getUrl(), 0, -strlen($matches[0])));
            }
        });
    }
}
