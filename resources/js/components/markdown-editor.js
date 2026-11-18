const TOOLBAR = [
    'bold', 'italic', 'heading', '|',
    'quote', 'unordered-list', 'ordered-list', 'checklist', '|',
    'link', 'image', 'code', 'table', '|',
    'preview', 'side-by-side', 'fullscreen', '|',
    'guide',
];

async function initEditor(element) {
    const autosaveId = element.dataset.autosaveId;
    const { default: EasyMDE } = await import('easymde');

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
    root.querySelectorAll('textarea[data-markdown-editor]').forEach((element) => {
        initEditor(element).catch((error) => console.error('Error initializing markdown editor:', error));
    });
}

document.addEventListener('DOMContentLoaded', () => initMarkdownEditors());
