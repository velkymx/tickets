import { beforeEach, describe, expect, it, vi } from 'vitest';
import initBulkSelectors from './bulk-select.js';

// jsdom has no layout engine, so visibility is driven by the same
// data-pane markers plus breakpoint stub the module reads.
function setDesktop(desktop) {
    window.matchMedia = vi.fn().mockReturnValue({
        matches: desktop,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
    });
}

function mount() {
    document.body.innerHTML = `
        <form data-bulk-select>
            <input type="checkbox" id="selectAll">
            <button type="button" id="checkAll">Check All</button>
            <div data-pane="table">
                <input type="checkbox" name="tickets[1]" value="1">
                <input type="checkbox" name="tickets[2]" value="2">
            </div>
            <div data-pane="cards">
                <input type="checkbox" name="tickets[1]" value="1">
                <input type="checkbox" name="tickets[2]" value="2">
            </div>
        </form>`;
    initBulkSelectors(document);
    const boxes = (pane) => [...document.querySelectorAll(`[data-pane="${pane}"] input`)];
    return {
        selectAll: document.querySelector('#selectAll'),
        toggle: document.querySelector('#checkAll'),
        table: boxes('table'),
        cards: boxes('cards'),
    };
}

function tick(box) {
    box.checked = true;
    box.dispatchEvent(new Event('change', { bubbles: true }));
}

describe('bulk-select twin sync', () => {
    beforeEach(() => setDesktop(true));

    it('mirrors a check to the hidden twin', () => {
        const { cards, table } = mount();

        tick(cards[0]);

        expect(table[0].checked).toBe(true);
        expect(table[1].checked).toBe(false);
    });

    it('mirrors an uncheck to the hidden twin', () => {
        const { cards, table } = mount();

        tick(cards[0]);
        cards[0].checked = false;
        cards[0].dispatchEvent(new Event('change', { bubbles: true }));

        expect(table[0].checked).toBe(false);
    });
});

describe('bulk-select visible pane state', () => {
    it('reflects only the desktop pane on wide screens', () => {
        setDesktop(true);
        const { selectAll, table } = mount();

        tick(table[0]);
        tick(table[1]);

        expect(selectAll.checked).toBe(true);
    });

    it('stays unchecked until every visible box is ticked', () => {
        setDesktop(true);
        const { selectAll, table } = mount();

        tick(table[0]);

        expect(selectAll.checked).toBe(false);
    });

    it('reflects only the cards pane on narrow screens', () => {
        setDesktop(false);
        const { cards, selectAll } = mount();

        tick(cards[0]);
        tick(cards[1]);

        expect(selectAll.checked).toBe(true);
    });

    it('select-all writes every twin for submission', () => {
        setDesktop(true);
        const { cards, selectAll, table } = mount();

        selectAll.checked = true;
        selectAll.dispatchEvent(new Event('change', { bubbles: true }));

        expect([...table, ...cards].every((box) => box.checked)).toBe(true);
    });

    it('toggle flips the label and checks all', () => {
        setDesktop(true);
        const { cards, toggle } = mount();

        toggle.dispatchEvent(new Event('click', { bubbles: true }));

        expect(cards.every((box) => box.checked)).toBe(true);
        expect(toggle.textContent).toBe('Uncheck All');
    });
});
