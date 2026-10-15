import EasyMDE from 'easymde';

const TOOLBAR = [
    'bold', 'italic', 'heading', '|',
    'quote', 'unordered-list', 'ordered-list', 'checklist', '|',
    'link', 'image', 'code', 'table', '|',
    'preview', 'side-by-side', 'fullscreen', '|',
    'guide',
];

function initEditor(element) {
    const autosaveId = element.dataset.autosaveId;

    new EasyMDE({
        element,
        spellChecker: false,
        minHeight: '350px',
        placeholder: element.dataset.placeholder || '',
        autosave: autosaveId
            ? { enabled: true, uniqueId: autosaveId, delay: 5000 }
            : { enabled: false },
        toolbar: TOOLBAR,
        status: ['lines', 'words'],
    });
}

export default function initMarkdownEditors(root = document) {
    root.querySelectorAll('textarea[data-markdown-editor]').forEach(initEditor);
}

document.addEventListener('DOMContentLoaded', () => initMarkdownEditors());
