import { Modal } from 'bootstrap';

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

function hideNote(noteid) {
    const noteElement = document.getElementById('note_' + noteid);
    if (!noteElement) return;

    fetch('/notes/hide/' + noteid, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf(),
            'Content-Type': 'application/json',
        },
    })
        .then(response => {
            if (!response.ok) {
                console.error('Failed to hide note');
                return;
            }

            noteElement.style.transition = 'opacity 0.5s ease-out, height 0.5s ease-out';
            noteElement.style.opacity = '0';
            noteElement.style.height = noteElement.offsetHeight + 'px';

            setTimeout(() => {
                noteElement.style.height = '0';
                noteElement.style.margin = '0';
            }, 100);

            setTimeout(() => {
                noteElement.remove();
            }, 600);
        })
        .catch(error => console.error('Error hiding note:', error));
}

function makeDescriptionImagesResponsive() {
    document.querySelectorAll('.ticket-description-content img').forEach(img => {
        img.classList.add('img-fluid');
    });
}

function wireKeyboardShortcuts() {
    let currentCommentIndex = -1;

    function navigateComments(direction) {
        const entries = document.querySelectorAll('.timeline-entry');
        if (!entries.length) return;
        currentCommentIndex = Math.max(0, Math.min(entries.length - 1, currentCommentIndex + direction));
        entries[currentCommentIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
        entries[currentCommentIndex].classList.add('ring');
        setTimeout(() => entries[currentCommentIndex].classList.remove('ring'), 1000);
    }

    document.addEventListener('keydown', function (e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            if (e.key === 'Escape') {
                e.target.blur();
            }
            return;
        }

        switch (e.key) {
            case '?': {
                e.preventDefault();
                const modal = document.getElementById('composerHelpModal');
                if (modal) new Modal(modal).show();
                break;
            }
            case 'r':
                e.preventDefault();
                document.getElementById('note-textarea')?.focus();
                break;
            case '/': {
                e.preventDefault();
                const textarea = document.getElementById('note-textarea');
                if (textarea) {
                    textarea.focus();
                    textarea.value = '/';
                }
                break;
            }
            case 'j':
                e.preventDefault();
                navigateComments(1);
                break;
            case 'k':
                e.preventDefault();
                navigateComments(-1);
                break;
        }
    });
}

function wireReactionForms() {
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('.reaction-toggle-form');
        if (!form) return;

        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        })
            .then(async response => {
                if (!response.ok) {
                    throw new Error('Failed to toggle reaction');
                }
                return response.json();
            })
            .then(data => {
                const currentBar = form.closest('.reaction-bar');
                if (currentBar && data.html) {
                    currentBar.outerHTML = data.html;
                }
            })
            .catch(error => {
                console.error('Error toggling reaction:', error);
            })
            .finally(() => {
                if (submitButton) submitButton.disabled = false;
            });
    });
}

function wireReplyForms() {
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('.reply-form');
        if (!form) return;

        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const textarea = form.querySelector('textarea[name="body"]');
        if (submitButton) submitButton.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error((data.errors && Object.values(data.errors).flat()[0]) || 'Failed to add reply');
                }
                return data;
            })
            .then(data => {
                const noteCard = form.closest('.card');
                const composer = form.closest('.reply-composer');
                const existingReplies = noteCard?.querySelector('.replies-section');

                if (existingReplies && data.replies_html) {
                    existingReplies.outerHTML = data.replies_html;
                } else if (composer && data.replies_html) {
                    composer.insertAdjacentHTML('beforebegin', data.replies_html);
                }

                form.reset();
                textarea?.focus();
            })
            .catch(error => {
                console.error('Error adding reply:', error);
            })
            .finally(() => {
                if (submitButton) submitButton.disabled = false;
            });
    });
}

export default function initTimeline() {
    window.hideNote = hideNote;
    makeDescriptionImagesResponsive();
    wireKeyboardShortcuts();
    wireReactionForms();
    wireReplyForms();
}
