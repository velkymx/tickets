import { describe, expect, it } from 'vitest';
import { csrfToken, withCsrf } from './csrf.js';

describe('csrf helper', () => {
    it('reads the token from the meta tag', () => {
        document.head.innerHTML = '<meta name="csrf-token" content="test-token">';

        expect(csrfToken()).toBe('test-token');
    });

    it('falls back to an empty token without a meta tag', () => {
        document.head.innerHTML = '';

        expect(csrfToken()).toBe('');
    });

    it('merges csrf and accept headers with overrides', () => {
        document.head.innerHTML = '<meta name="csrf-token" content="test-token">';

        expect(withCsrf()).toEqual({
            'X-CSRF-TOKEN': 'test-token',
            Accept: 'application/json',
        });
        expect(withCsrf({ 'Content-Type': 'application/json' })['Content-Type']).toBe('application/json');
    });
});
