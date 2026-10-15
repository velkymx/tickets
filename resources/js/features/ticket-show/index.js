import initComposer from './composer';
import initTimeline from './timeline';
import initPresence from './presence';

function onReady(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn);
    } else {
        fn();
    }
}

onReady(() => {
    if (!document.querySelector('[data-ticket-show]')) {
        return;
    }

    initTimeline();
    initComposer();
    initPresence();
});
