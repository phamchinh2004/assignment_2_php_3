class AuthorizationStore {
    constructor(initialState, endpoint, fallbackUrl) {
        this.state = initialState || {};
        this.endpoint = endpoint;
        this.fallbackUrl = fallbackUrl;
        this.refreshSequence = 0;
        this.listening = false;
    }

    can(permission) {
        if (!permission) {
            return true;
        }

        return Boolean(this.state.is_superuser)
            || (this.state.permissions || []).includes(permission);
    }

    canAny(permissions) {
        const list = this.normalize(permissions);
        return list.length === 0 || list.some((permission) => this.can(permission));
    }

    canAll(permissions) {
        const list = this.normalize(permissions);
        return list.length === 0 || list.every((permission) => this.can(permission));
    }

    apply(root = document) {
        root.querySelectorAll('[data-permission], [data-permission-any], [data-permission-all]').forEach((element) => {
            let allowed = true;

            if (element.dataset.permission) {
                allowed = this.can(element.dataset.permission);
            } else if (element.dataset.permissionAny) {
                allowed = this.canAny(element.dataset.permissionAny);
            } else if (element.dataset.permissionAll) {
                allowed = this.canAll(element.dataset.permissionAll);
            }

            element.hidden = !allowed;
        });
    }

    async refresh() {
        if (!this.endpoint) {
            return;
        }

        const sequence = ++this.refreshSequence;
        const oldPermissions = new Set(this.state.permissions || []);
        const url = new URL(this.endpoint, window.location.origin);
        url.searchParams.set('path', window.location.pathname);

        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            if (response.status === 401 || response.status === 403) {
                window.location.reload();
            }
            return;
        }

        const nextState = await response.json();
        if (sequence !== this.refreshSequence) {
            return;
        }

        const nextPermissions = new Set(nextState.permissions || []);
        const changedPermissions = new Set([
            ...[...oldPermissions].filter((permission) => !nextPermissions.has(permission)),
            ...[...nextPermissions].filter((permission) => !oldPermissions.has(permission)),
        ]);

        this.state = nextState;
        this.apply();

        window.dispatchEvent(new CustomEvent('authorization:updated', {
            detail: nextState,
        }));

        if (nextState.route && nextState.route.can_access === false) {
            window.location.assign(this.fallbackUrl);
            return;
        }

        const refreshPermissions = nextState.route?.refresh_permissions || [];
        if (refreshPermissions.some((permission) => changedPermissions.has(permission))) {
            window.location.reload();
        }
    }

    listenRealtime() {
        if (this.listening || !window.Echo || !window.Laravel?.userId) {
            return;
        }

        this.listening = true;
        window.Echo
            .private(`staff.${window.Laravel.userId}`)
            .listen('.AuthorizationUpdated', (event) => {
                if (event?.version && event.version === this.state.version) {
                    return;
                }

                this.refresh().catch((error) => {
                    console.error('Unable to refresh authorization state', error);
                });
            });
    }

    normalize(permissions) {
        if (Array.isArray(permissions)) {
            return permissions.filter(Boolean);
        }

        return String(permissions || '')
            .split(',')
            .map((permission) => permission.trim())
            .filter(Boolean);
    }
}

const store = new AuthorizationStore(
    window.__authorizationBootstrap,
    window.__authorizationEndpoint,
    window.__authorizationFallbackUrl || '/admin/chat-panel'
);

window.authorization = {
    can: (permission) => store.can(permission),
    cannot: (permission) => !store.can(permission),
    canAny: (permissions) => store.canAny(permissions),
    canAll: (permissions) => store.canAll(permissions),
    refresh: () => store.refresh(),
    state: () => store.state,
};

document.addEventListener('DOMContentLoaded', () => {
    store.apply();
    store.listenRealtime();
});

window.addEventListener('load', () => {
    store.listenRealtime();
});
