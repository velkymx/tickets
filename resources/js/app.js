import './bootstrap';

import './components/rich-text';

import './components/markdown-editor';

import Alpine from 'alpinejs';

import { actionBuilder, conditionBuilder } from './features/automation-builders';

window.Alpine = Alpine;

Alpine.data('conditionBuilder', conditionBuilder);
Alpine.data('actionBuilder', actionBuilder);

Alpine.start();

import './components/BurndownChart';

import './features/ticket-show';

import './features/bulk-select';
