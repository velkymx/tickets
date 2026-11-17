import { describe, expect, it, vi } from 'vitest';
import initRichTextEditors from '../components/rich-text.js';

const created = [];

vi.mock('@tiptap/core', async (importOriginal) => {
    const mod = await importOriginal();

    return {
        ...mod,
        Editor: class extends mod.Editor {
            constructor(options) {
                super(options);
                created.push(this);
            }
        },
    };
});

describe('rich text underline', () => {
    it('underlines the selected text instead of throwing', () => {
        document.body.innerHTML =
            '<textarea id="body"></textarea><div data-rich-text data-target="#body"></div>';
        initRichTextEditors(document);

        const editor = created.at(-1);
        editor.commands.setContent('Hello');
        editor.commands.focus();
        editor.commands.selectAll();

        const button = document.querySelector('button[title="Underline"]');
        expect(button).not.toBeNull();
        expect(() => button.click()).not.toThrow();

        expect(document.querySelector('#body').value).toContain('<u>Hello</u>');
    });
});

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
