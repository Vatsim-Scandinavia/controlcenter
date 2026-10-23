import assert from 'node:assert/strict';
import test from 'node:test';
import { reportImages, resizeImageMarkup, imagePreviewMarkdown } from '../../resources/js/report-image-markdown.js';
import { draggedImageWidth } from '../../resources/js/report-images.js';

test('images inside code are not confused with an identical real image', () => {
    const image = '![Radar](https://example.test/radar.png)';
    const markdown = ['~~~markdown', image, '~~~', String.fromCharCode(96) + image + String.fromCharCode(96), '', image].join('\n');
    const images = reportImages(markdown);
    assert.equal(images.length, 1);
    assert.equal(images[0].start, markdown.lastIndexOf(image));
});

test('source positions survive lists, quotes, tables and repeated images', () => {
    const markdown = '> ![Quote](quote.png)\n\n- ![List](list.png)\n\n| Image |\n| --- |\n| ![Table](table.png) |\n\n![Repeated](list.png)';
    const images = reportImages(markdown);
    assert.deepEqual(images.map(image => image.url), ['quote.png', 'list.png', 'table.png', 'list.png']);
    for (const image of images) assert.equal(markdown.slice(image.start, image.end), image.markup);
});

test('resizing preserves parentheses, escaped URLs, titles and angle brackets', () => {
    for (const markdown of [
        '![Radar](https://example.test/radar(session).png "Session image")',
        "![Radar](<https://example.test/radar.png> 'Session image')",
        '![Radar](https://example.test/radar\\(session\\).png)',
    ]) {
        const [image] = reportImages(markdown);
        assert.ok(image, markdown);
        const resized = resizeImageMarkup(image, 400);
        assert.match(resized, /#report-image-width=400/);
        const [resizedImage] = reportImages(resized);
        assert.equal(resizeImageMarkup(resizedImage, ''), markdown);
    }
});

test('local preview URLs change only image destinations, never surrounding code or prose', () => {
    const url = 'inline-image:pending';
    const markdown = 'The marker ' + url + '\n\n~~~markdown\n![Example](' + url + ')\n~~~\n\n![Image](' + url + ')';
    const preview = imagePreviewMarkdown(markdown, reportImages(markdown), value => value.replace(url, 'blob:preview'));
    assert.match(preview, /!\[Example\]\(inline-image:pending\)/);
    assert.match(preview, /The marker inline-image:pending/);
    assert.match(preview, /!\[Image\]\(blob:preview\)/);
});

test('reference-style images are left unchanged rather than resizing their labels', () => {
    assert.deepEqual(reportImages('![Radar][radar]\n\n[radar]: https://example.test/radar.png'), []);
});

test('all four corners preserve proportional resizing', () => {
    for (const [corner, horizontal, vertical] of [['se', 40, 20], ['sw', -40, 20], ['ne', 40, -20], ['nw', -40, -20]]) {
        assert.equal(draggedImageWidth(320, 160, corner, horizontal, vertical), 360);
        assert.equal(draggedImageWidth(320, 160, corner, -horizontal, -vertical), 280);
    }
    assert.equal(draggedImageWidth(320, 160, 'se', -1000, -500), 1);
    assert.equal(draggedImageWidth(320, 160, 'se', 100000, 50000), 99999);
});
