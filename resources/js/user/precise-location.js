(() => {
    const config = window.preciseLocationConfig || {};
    if (!config.endpoint || !config.csrf || !config.userId || !navigator.geolocation) return;

    const requestKey = `precise-location-requested:${config.userId}:${config.loginAt || 'current'}`;
    if (window.sessionStorage.getItem(requestKey) === '1') return;

    window.sessionStorage.setItem(requestKey, '1');

    async function saveLocation(payload) {
        try {
            await fetch(config.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });
        } catch (error) {
            console.warn('Không thể cập nhật vị trí hiện tại.', error);
        }
    }

    navigator.geolocation.getCurrentPosition(async (position) => {
        const payload = {
            permission: 'granted',
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy || null,
            country_code: null,
            country: null,
            city: null,
        };

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${payload.latitude}&lon=${payload.longitude}&zoom=10`, {
                headers: { 'Accept-Language': document.documentElement.lang || 'vi' },
            });
            const address = (await response.json()).address || {};
            payload.country_code = (address.country_code || '').toUpperCase() || null;
            payload.country = address.country || null;
            payload.city = address.city || address.town || address.village || null;
        } catch (error) {
            console.warn('Không thể xác định tên khu vực từ tọa độ.', error);
        }

        await saveLocation(payload);
    }, async (error) => {
        if (error?.code === 1) {
            await saveLocation({ permission: 'denied' });
        }
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
    });
})();
