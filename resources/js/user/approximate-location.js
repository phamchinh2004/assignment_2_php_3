(() => {
    const config = window.approximateLocationConfig || {};
    if (!config.endpoint || !config.csrf) return;

    let updating = false;

    async function refreshApproximateLocation(force = false) {
        if (updating || !navigator.onLine) return;

        updating = true;
        try {
            await fetch(config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ force }),
            });
        } catch (error) {
            console.warn('Không thể cập nhật vị trí tương đối.', error);
        } finally {
            updating = false;
        }
    }

    refreshApproximateLocation();
    window.setInterval(() => refreshApproximateLocation(), config.intervalMs || 600000);
    window.addEventListener('online', () => refreshApproximateLocation());
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refreshApproximateLocation();
        }
    });

    window.addEventListener('load', () => {
        if (!window.Echo || !window.userId) return;

        window.Echo.private(`user.${window.userId}`)
            .listen('.ApproximateLocationRefreshRequested', () => {
                refreshApproximateLocation(true);
            });
    });
})();
