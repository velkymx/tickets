import _ from 'lodash';
window._ = _;

import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import $ from 'jquery';
window.$ = window.jQuery = $;

import 'jquery-ui/ui/widgets/datepicker';
import 'jquery-validation';

import 'bootstrap';

import '@fortawesome/fontawesome-free/css/all.min.css';

import 'quill/dist/quill.snow.css';
import Quill from 'quill';
window.Quill = Quill;
