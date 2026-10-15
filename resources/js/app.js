import './bootstrap';

import './components/rich-text';

import EasyMDE from 'easymde';
window.EasyMDE = EasyMDE;

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import './components/BurndownChart';
