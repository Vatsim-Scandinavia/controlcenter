import { WIDTH_SUFFIX, reportImages, resizeImageMarkup, imagePreviewMarkdown } from './report-image-markdown.js';
const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const CORNERS = { nw: 'top left', ne: 'top right', sw: 'bottom left', se: 'bottom right' };

export function draggedImageWidth(startWidth, startHeight, corner, deltaX, deltaY) {
    const horizontal = deltaX * (corner.endsWith('w') ? -1 : 1);
    const vertical = deltaY * (corner.startsWith('n') ? -1 : 1) * startWidth / Math.max(1, startHeight);
    const delta = Math.abs(horizontal) >= Math.abs(vertical) ? horizontal : vertical;
    return Math.max(1, Math.min(99999, Math.round(startWidth + delta)));
}

export function createReportImageManager(form, status) {
    const pendingImages = new Map();
    const selectedIndexes = new WeakMap();
    let imageCounter = 0;
    let drag = null;

    function showStatus(message, isError = false) {
        status.textContent = message;
        status.classList.toggle('text-danger', isError);
    }

    function registerImage(file, input) {
        if (file.type && !IMAGE_TYPES.includes(file.type)) {
            input?.remove();
            showStatus('Choose a PNG, JPEG or WebP image.', true);
            return null;
        }
        const token = 'image-' + Date.now() + '-' + (++imageCounter);
        if (!input) {
            input = document.createElement('input');
            input.type = 'file';
            input.hidden = true;
            try {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
            } catch {
                showStatus('Your browser cannot attach pasted images. Use Insert image instead.', true);
                return null;
            }
            form.appendChild(input);
        }
        input.name = 'inline_images[' + token + ']';
        pendingImages.set(token, { input, url: URL.createObjectURL(file) });
        const alt = (file.name || 'Image').replace(/\.[^.]+$/, '').replace(/[\\\[\]<>\r\n]/g, ' ');
        return '![' + alt + '](inline-image:' + token + ')';
    }

    function insertImage(editor) {
        const cursor = editor.codemirror.getCursor();
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = IMAGE_TYPES.join(',');
        input.hidden = true;
        form.appendChild(input);
        input.addEventListener('cancel', () => input.remove());
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) {
                input.remove();
                return;
            }
            const markdown = registerImage(file, input);
            if (markdown) {
                editor.codemirror.replaceRange(markdown, cursor);
                editor.codemirror.focus();
                showStatus('Image inserted. Open Preview to drag its corners and resize it.');
            }
        });
        input.click();
    }

    function commitWidth(editor, image, width) {
        if (editor.value().slice(image.start, image.end) !== image.markup) {
            showStatus('The image Markdown changed. Select the image again to resize it.', true);
            return;
        }
        editor.codemirror.replaceRange(resizeImageMarkup(image, width),
            editor.codemirror.posFromIndex(image.start), editor.codemirror.posFromIndex(image.end));
        const container = editor.codemirror.getWrapperElement().parentElement;
        container.querySelectorAll('.editor-preview-active, .editor-preview-active-side').forEach(preview => {
            preview.innerHTML = editor.options.previewRender(editor.value());
        });
        showStatus('Image resized to ' + width + ' pixels wide. Save the report to keep this size.');
    }

    function selectFrame(editor, frame) {
        const index = Number(frame.dataset.reportImageIndex);
        selectedIndexes.set(editor, index);
        const container = editor.codemirror.getWrapperElement().parentElement;
        container.querySelectorAll('.report-image-frame').forEach(candidate => {
            candidate.classList.toggle('is-selected', Number(candidate.dataset.reportImageIndex) === index);
        });
    }

    function finishDrag(cancelled = false) {
        if (!drag) return;
        const current = drag;
        drag = null;
        if (current.handle.hasPointerCapture(current.pointerId)) current.handle.releasePointerCapture(current.pointerId);
        current.frame.classList.remove('is-resizing');
        if (cancelled) {
            current.frame.style.width = current.originalWidth;
            showStatus('Image resizing cancelled.');
        } else {
            commitWidth(current.editor, current.image, current.width);
        }
    }

    window.addEventListener('pagehide', event => {
        if (!event.persisted) pendingImages.forEach(image => URL.revokeObjectURL(image.url));
    });

    function previewUrl(url) {
        return url.replace(/inline-image:([a-zA-Z0-9-]+)/g, (placeholder, token) =>
            pendingImages.has(token) ? pendingImages.get(token).url : placeholder);
    }

    return {
        toolbarButton: {
            name: 'insert-report-image',
            action: insertImage,
            className: 'fa fa-image',
            title: 'Insert image',
        },
        configurePreview(editor) {
            editor.options.previewRender = text => {
                const template = document.createElement('template');
                const images = reportImages(text);
                template.innerHTML = editor.markdown(imagePreviewMarkdown(text, images, previewUrl));
                const used = new Set();
                template.content.querySelectorAll('img').forEach(element => {
                    const source = element.getAttribute('src');
                    const index = images.findIndex((image, index) => !used.has(index) && previewUrl(image.url) === source);
                    const width = source?.match(WIDTH_SUFFIX);
                    if (width) {
                        element.setAttribute('width', width[1]);
                        element.setAttribute('src', source.replace(WIDTH_SUFFIX, ''));
                    }
                    if (index < 0) return;
                    used.add(index);
                    const frame = document.createElement('span');
                    frame.className = 'report-image-frame';
                    frame.classList.toggle('is-selected', selectedIndexes.get(editor) === index);
                    frame.dataset.reportImageIndex = index;
                    if (width) frame.style.width = width[1] + 'px';
                    element.setAttribute('tabindex', '0');
                    element.setAttribute('role', 'button');
                    element.setAttribute('aria-label', 'Select image to resize: ' + (element.getAttribute('alt') || 'Image'));
                    element.draggable = false;
                    element.replaceWith(frame);
                    frame.appendChild(element);
                    Object.entries(CORNERS).forEach(([corner, label]) => {
                        const handle = document.createElement('button');
                        handle.type = 'button';
                        handle.className = 'report-image-handle report-image-handle-' + corner;
                        handle.dataset.resizeCorner = corner;
                        handle.setAttribute('aria-label', 'Resize image from ' + label + ' corner');
                        handle.title = 'Drag to resize; arrow keys adjust size';
                        frame.appendChild(handle);
                    });
                });
                return template.innerHTML;
            };
            editor.codemirror.on('paste', (codemirror, event) => {
                const clipboard = event.clipboardData;
                if (!clipboard) return;
                const items = Array.from(clipboard.items || []);
                const fileItems = items.filter(item => item.kind === 'file');
                const files = fileItems.length ? fileItems.map(item => item.getAsFile()).filter(Boolean) : Array.from(clipboard.files || []);
                const images = files.filter(file => file.type.startsWith('image/'));
                if (!images.length) return;
                event.preventDefault();
                const markdown = images.map(file => registerImage(file)).filter(Boolean).join('\n');
                if (markdown) {
                    codemirror.replaceSelection(markdown, 'end', 'paste');
                    showStatus('Image pasted. Open Preview to drag its corners and resize it.');
                }
            });
            const container = editor.codemirror.getWrapperElement().parentElement;
            container.addEventListener('click', event => {
                const frame = event.target.closest('.report-image-frame');
                if (frame) {
                    event.preventDefault();
                    selectFrame(editor, frame);
                } else {
                    selectedIndexes.delete(editor);
                    container.querySelectorAll('.report-image-frame').forEach(frame => frame.classList.remove('is-selected'));
                }
            });
            container.addEventListener('pointerdown', event => {
                const handle = event.target.closest('[data-resize-corner]');
                if (!handle || event.button !== 0 || drag) return;
                const frame = handle.closest('.report-image-frame');
                const image = reportImages(editor.value())[Number(frame.dataset.reportImageIndex)];
                if (!image) return;
                event.preventDefault();
                selectFrame(editor, frame);
                const bounds = frame.getBoundingClientRect();
                drag = {
                    editor, frame, handle, image,
                    pointerId: event.pointerId,
                    corner: handle.dataset.resizeCorner,
                    startX: event.clientX,
                    startY: event.clientY,
                    startWidth: bounds.width,
                    startHeight: bounds.height,
                    originalWidth: frame.style.width,
                    width: Math.round(bounds.width),
                };
                frame.classList.add('is-resizing');
                handle.setPointerCapture(event.pointerId);
            });
            container.addEventListener('pointermove', event => {
                if (!drag || event.pointerId !== drag.pointerId) return;
                event.preventDefault();
                drag.width = draggedImageWidth(drag.startWidth, drag.startHeight, drag.corner,
                    event.clientX - drag.startX, event.clientY - drag.startY);
                drag.frame.style.width = drag.width + 'px';
            });
            container.addEventListener('pointerup', event => {
                if (drag && event.pointerId === drag.pointerId) finishDrag();
            });
            ['pointercancel', 'lostpointercapture'].forEach(name => container.addEventListener(name, event => {
                if (drag && event.pointerId === drag.pointerId) finishDrag(true);
            }));
            container.addEventListener('keydown', event => {
                const frame = event.target.closest('.report-image-frame');
                if (event.key === 'Escape') {
                    finishDrag(true);
                    selectedIndexes.delete(editor);
                    container.querySelectorAll('.report-image-frame').forEach(frame => frame.classList.remove('is-selected'));
                    return;
                }
                if (!frame) return;
                if (['Enter', ' '].includes(event.key)) {
                    event.preventDefault();
                    selectFrame(editor, frame);
                    return;
                }
                if (!event.target.matches('[data-resize-corner]') || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
                event.preventDefault();
                selectFrame(editor, frame);
                const image = reportImages(editor.value())[Number(frame.dataset.reportImageIndex)];
                const change = (event.shiftKey ? 1 : 10) * (['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1);
                const width = Math.max(1, Math.min(99999, Math.round(frame.getBoundingClientRect().width + change)));
                const corner = event.target.dataset.resizeCorner;
                if (image) {
                    commitWidth(editor, image, width);
                    container.querySelector('.report-image-frame.is-selected [data-resize-corner="' + corner + '"]')?.focus();
                }
            });
        },
        prepareSubmit(event, editors) {
            if (drag) finishDrag();
            const content = editors.map(editor => editor.value()).join('\n');
            const tokens = Array.from(content.matchAll(/inline-image:([a-zA-Z0-9-]+)/g), match => match[1]);
            if (tokens.some(token => !pendingImages.has(token))) {
                event.preventDefault();
                showStatus('Please remove and reinsert the unsaved images before saving the report.', true);
                return;
            }
            pendingImages.forEach((image, token) => {
                image.input.disabled = !tokens.includes(token);
            });
        },
    };
}
