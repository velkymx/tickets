import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';

const BUTTONS = [
    { command: 'toggleBold', icon: 'fa-bold', title: 'Bold', active: 'bold' },
    { command: 'toggleItalic', icon: 'fa-italic', title: 'Italic', active: 'italic' },
    { command: 'toggleUnderline', icon: 'fa-underline', title: 'Underline', active: 'underline' },
    { command: 'toggleStrike', icon: 'fa-strikethrough', title: 'Strikethrough', active: 'strike' },
    { separator: true },
    { command: 'toggleHeading', args: { level: 1 }, label: 'H1', title: 'Heading 1', active: 'heading', activeArgs: { level: 1 } },
    { command: 'toggleHeading', args: { level: 2 }, label: 'H2', title: 'Heading 2', active: 'heading', activeArgs: { level: 2 } },
    { command: 'toggleHeading', args: { level: 3 }, label: 'H3', title: 'Heading 3', active: 'heading', activeArgs: { level: 3 } },
    { separator: true },
    { command: 'toggleBulletList', icon: 'fa-list-ul', title: 'Bullet list', active: 'bulletList' },
    { command: 'toggleOrderedList', icon: 'fa-list-ol', title: 'Numbered list', active: 'orderedList' },
    { command: 'toggleBlockquote', icon: 'fa-quote-right', title: 'Blockquote', active: 'blockquote' },
    { command: 'toggleCodeBlock', icon: 'fa-code', title: 'Code block', active: 'codeBlock' },
    { separator: true },
    { action: 'link', icon: 'fa-link', title: 'Insert link' },
    { action: 'image', icon: 'fa-image', title: 'Insert image' },
    { action: 'clear', icon: 'fa-eraser', title: 'Clear formatting' },
];

function buildButton(spec) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm btn-outline-secondary rich-text-btn';
    button.title = spec.title;

    if (spec.icon) {
        const icon = document.createElement('i');
        icon.className = `fas ${spec.icon}`;
        button.appendChild(icon);
    } else {
        button.textContent = spec.label;
    }

    return button;
}

function runButton(editor, spec) {
    const chain = editor.chain().focus();

    if (spec.command) {
        chain[spec.command](spec.args).run();

        return;
    }

    if (spec.action === 'link') {
        const previous = editor.getAttributes('link').href || '';
        const url = window.prompt('Link URL', previous);

        if (url === null) {
            return;
        }

        if (url === '') {
            chain.extendMarkRange('link').unsetLink().run();

            return;
        }

        chain.extendMarkRange('link').setLink({ href: url }).run();

        return;
    }

    if (spec.action === 'image') {
        const url = window.prompt('Image URL');

        if (url) {
            chain.setImage({ src: url }).run();
        }

        return;
    }

    if (spec.action === 'clear') {
        chain.unsetAllMarks().clearNodes().run();
    }
}

function isActive(editor, spec) {
    if (!spec.active) {
        return false;
    }

    return editor.isActive(spec.active, spec.activeArgs || {});
}

function initEditor(element) {
    const target = document.querySelector(element.dataset.target);

    if (!target) {
        return;
    }

    const toolbar = document.createElement('div');
    toolbar.className = 'rich-text-toolbar btn-toolbar gap-1 mb-2';

    const mount = document.createElement('div');
    mount.className = 'rich-text-surface';

    element.classList.add('rich-text');
    element.appendChild(toolbar);
    element.appendChild(mount);

    const editor = new Editor({
        element: mount,
        extensions: [
            StarterKit.configure({
                link: { openOnClick: false },
            }),
            Image,
            Placeholder.configure({
                placeholder: element.dataset.placeholder || '',
            }),
        ],
        content: target.value || '',
        onUpdate: ({ editor }) => {
            target.value = editor.isEmpty ? '' : editor.getHTML();
        },
    });

    const controls = [];

    BUTTONS.forEach((spec) => {
        if (spec.separator) {
            const divider = document.createElement('span');
            divider.className = 'rich-text-divider';
            toolbar.appendChild(divider);

            return;
        }

        const button = buildButton(spec);
        button.addEventListener('click', () => runButton(editor, spec));
        toolbar.appendChild(button);
        controls.push({ button, spec });
    });

    const syncActive = () => {
        controls.forEach(({ button, spec }) => {
            button.classList.toggle('active', isActive(editor, spec));
        });
    };

    editor.on('selectionUpdate', syncActive);
    editor.on('transaction', syncActive);
    syncActive();

    const form = element.closest('form');

    if (form) {
        form.addEventListener('submit', () => {
            target.value = editor.isEmpty ? '' : editor.getHTML();
        });
    }
}

export default function initRichTextEditors(root = document) {
    root.querySelectorAll('[data-rich-text]').forEach(initEditor);
}

document.addEventListener('DOMContentLoaded', () => initRichTextEditors());
