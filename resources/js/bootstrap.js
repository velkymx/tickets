import _ from 'lodash';
window._ = _;

import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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
