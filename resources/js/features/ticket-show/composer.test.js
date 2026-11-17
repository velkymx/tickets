import { describe, expect, it, vi } from 'vitest';
import initComposer from './composer.js';

describe('composer guards', () => {
    it('ignores pages without a composer', () => {
        document.body.innerHTML = '<p>No composer here</p>';

        expect(() => initComposer(document)).not.toThrow();
    });

    it('wires document listeners only once', () => {
        document.body.innerHTML = `
            <div class="markdown-composer">
                <textarea id="note-textarea"></textarea>
                <div class="mention-autocomplete d-none"></div>
            </div>`;

        const spy = vi.spyOn(document, 'addEventListener');
        initComposer(document);
        const firstAdds = spy.mock.calls.filter(([type]) => type === 'click').length;
        initComposer(document);
        const secondAdds = spy.mock.calls.filter(([type]) => type === 'click').length;
        spy.mockRestore();

        expect(firstAdds).toBeGreaterThan(0);
        expect(secondAdds).toBe(firstAdds);
    });

    it('wraps the selection once even after a double init', () => {
        document.body.innerHTML = `
            <div class="markdown-composer">
                <textarea id="note-textarea"></textarea>
                <div class="mention-autocomplete d-none"></div>
                <button type="button" data-md="**">B</button>
            </div>
        `;
        initComposer(document);
        initComposer(document);

        const textarea = document.querySelector('#note-textarea');
        textarea.value = 'x';
        textarea.selectionStart = 0;
        textarea.selectionEnd = 1;
        document.querySelector('[data-md]').click();

        expect(textarea.value).toBe('**x**');
    });
});
