import './bootstrap';

import Quill from 'quill';
window.Quill = Quill;

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import './components/BurndownChart';
