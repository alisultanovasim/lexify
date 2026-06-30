import { createApp, h } from 'vue';
import { createInertiaApp, Head, Link } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import axios from 'axios';
import '../css/app.css';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
// Use cookie-based CSRF (XSRF-TOKEN cookie → X-XSRF-TOKEN header).
// Laravel refreshes this cookie on every response, so it stays current
// even after Inertia client-side navigation (meta tag approach goes stale).
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

const appName = import.meta.env.VITE_APP_NAME || 'LinguaCards';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => {
        const [module, ...rest] = name.split('::');
        if (rest.length > 0) {
            return resolvePageComponent(
                `../../Modules/${module}/resources/js/Pages/${rest.join('/')}.vue`,
                import.meta.glob('../../Modules/**/resources/js/Pages/**/*.vue')
            );
        }
        return resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        );
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Head', Head)
            .component('Link', Link)
            .mount(el);
    },
    progress: { color: '#6366f1' },
});
