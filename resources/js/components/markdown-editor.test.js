import { describe, expect, it } from 'vitest';
import initMarkdownEditors from './markdown-editor.js';

describe('markdown editor', () => {
    it('ignores pages without a markdown textarea', () => {
        document.body.innerHTML = '<p>No editor here</p>';

        expect(() => initMarkdownEditors(document)).not.toThrow();
    });
});
