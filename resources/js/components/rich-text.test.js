import { describe, expect, it } from 'vitest';
import initRichTextEditors from '../components/rich-text.js';

describe('rich text toolbar', () => {
    it('keeps the editor selection when a toolbar button is pressed', () => {
        document.body.innerHTML =
            '<textarea id="body"></textarea><div data-rich-text data-target="#body"></div>';
        initRichTextEditors(document);

        const button = document.querySelector('[data-rich-text] button');
        expect(button).not.toBeNull();
        const event = new MouseEvent('mousedown', { bubbles: true, cancelable: true });
        button.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(true);
    });
});
