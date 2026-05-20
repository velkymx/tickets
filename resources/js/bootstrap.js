import 'bootstrap';

import '@fortawesome/fontawesome-free/css/all.min.css';

window.loadQuill = async function() {
    if (window.Quill) {
        return window.Quill;
    }
    const Quill = (await import('quill')).default;
    window.Quill = Quill;
    return Quill;
};
