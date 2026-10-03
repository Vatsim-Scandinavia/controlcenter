import { Lexer } from 'marked';

export const WIDTH_SUFFIX = /#report-image-width=([1-9][0-9]{0,4})$/;

export function reportImages(text) {
    const images = [];

    function visit(tokens, start, end) {
        let cursor = start;
        for (const token of tokens) {
            const raw = token.raw || '';
            const position = raw ? text.indexOf(raw, cursor) : -1;
            const found = position >= cursor && position + raw.length <= end;
            const tokenStart = found ? position : cursor;
            const tokenEnd = found ? position + raw.length : end;

            if (token.type === 'image' && found) {
                const destination = raw.match(/\]\(\s*(<[^>\n]*>|[\s\S]*?)(?:\s+(?:"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|\((?:\\.|[^)\\])*\)))?\s*\)$/);
                if (destination) {
                    images.push({
                        start: tokenStart,
                        end: tokenEnd,
                        markup: raw,
                        rawUrl: destination[1],
                        url: token.href,
                        urlStart: raw.indexOf(destination[1], destination.index + 2),
                    });
                }
            } else {
                const children = token.items || token.tokens || (token.type === 'table'
                    ? [...token.header, ...token.rows.flat()].flatMap(cell => cell.tokens) : []);
                visit(children, tokenStart, tokenEnd);
            }

            if (found) cursor = tokenEnd;
        }
    }

    visit(new Lexer().lex(text), 0, text.length);
    return images;
}

export function resizeImageMarkup(image, width) {
    const source = image.rawUrl.replace(/^<|>$/g, '');
    const url = source.replace(WIDTH_SUFFIX, '') + (width ? '#report-image-width=' + width : '');
    const rawUrl = image.rawUrl.startsWith('<') ? '<' + url + '>' : url;
    return image.markup.slice(0, image.urlStart) + rawUrl + image.markup.slice(image.urlStart + image.rawUrl.length);
}

export function imagePreviewMarkdown(text, images, resolveUrl) {
    return images.reduceRight((markdown, image) => {
        const url = resolveUrl(image.url);
        if (url === image.url) return markdown;
        const start = image.start + image.urlStart;
        const destination = image.rawUrl.startsWith('<') ? '<' + url + '>' : url;
        return markdown.slice(0, start) + destination + markdown.slice(start + image.rawUrl.length);
    }, text);
}
