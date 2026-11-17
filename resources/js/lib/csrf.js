export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function withCsrf(headers = {}) {
    return { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json', ...headers };
}
