import { useEffect, useMemo, useRef, useState } from 'react';
import { Spin } from 'antd';
import { useLocation, useNavigate } from 'react-router-dom';
import AppRoutes from './Route';
import { isSpaNavigationTarget, syncLegacyNavigationState } from './spa-navigation';

export default function App({ bootstrap }) {
    const location = useLocation();
    const navigate = useNavigate();
    const initialUrlKey = `${window.location.pathname}${window.location.search}`;
    const [refreshNonce, setRefreshNonce] = useState(0);
    const [pageState, setPageState] = useState({
        bootstrap,
        requestKey: `${initialUrlKey}|0`,
    });
    const [loading, setLoading] = useState(false);
    const notifiedBootstrapRef = useRef(bootstrap);
    const lastLoadedUrlRef = useRef(initialUrlKey);
    const urlKey = `${location.pathname}${location.search}`;
    const requestKey = `${urlKey}|${refreshNonce}`;
    const surface = pageState.bootstrap?.surface || bootstrap?.surface;

    useEffect(() => {
        syncLegacyNavigationState(surface, location.pathname);
    }, [surface, location.pathname]);

    useEffect(() => {
        if (pageState.bootstrap === notifiedBootstrapRef.current) return;
        notifiedBootstrapRef.current = pageState.bootstrap;

        const flash = pageState.bootstrap?.props?.flash || {};
        const notice = flash.success
            ? ['success', flash.success, 'Thành công!']
            : flash.error
                ? ['error', flash.error, 'Thông báo!']
                : flash.warning
                    ? ['warning', flash.warning, 'Cảnh báo!']
                    : null;

        if (notice && typeof window.notification === 'function') {
            window.notification(...notice);
        }
    }, [pageState.bootstrap, surface]);

    const targetUrl = useMemo(
        () => new URL(`${urlKey}${location.hash || ''}`, window.location.origin),
        [urlKey, location.hash],
    );

    useEffect(() => {
        const handleClick = (event) => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            const anchor = event.target.closest?.('a[href]');
            if (!anchor || anchor.dataset.noSpa !== undefined || anchor.hasAttribute('download')) return;
            if (anchor.target && anchor.target !== '_self') return;

            const rawHref = anchor.getAttribute('href');
            if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) return;

            const url = new URL(anchor.href, window.location.href);
            if (!isSpaNavigationTarget(url, surface)) return;

            event.preventDefault();
            navigate(`${url.pathname}${url.search}${url.hash}`);
        };

        document.addEventListener('click', handleClick, true);
        return () => document.removeEventListener('click', handleClick, true);
    }, [navigate, surface]);

    useEffect(() => {
        const spaNavigate = (url, options = {}) => {
            const target = new URL(url, window.location.href);
            if (!isSpaNavigationTarget(target, surface)) {
                window.location.assign(target.toString());
                return false;
            }

            navigate(`${target.pathname}${target.search}${target.hash}`, { replace: Boolean(options.replace) });
            return true;
        };

        const spaCommitBootstrap = (url, nextBootstrap, options = {}) => {
            const target = new URL(url, window.location.href);
            if (!isSpaNavigationTarget(target, surface)) return false;
            if (!nextBootstrap?.page || !nextBootstrap?.props || nextBootstrap.surface !== surface) return false;

            const nextUrlKey = `${target.pathname}${target.search}`;
            setPageState({
                bootstrap: nextBootstrap,
                requestKey: `${nextUrlKey}|${refreshNonce}`,
            });
            lastLoadedUrlRef.current = nextUrlKey;
            if (nextBootstrap.title) document.title = nextBootstrap.title;
            syncLegacyNavigationState(surface, target.pathname);
            navigate(`${target.pathname}${target.search}${target.hash}`, { replace: options.replace !== false });
            return true;
        };

        window.__spaNavigate = spaNavigate;
        window.__spaCommitBootstrap = spaCommitBootstrap;
        window.__spaRefresh = () => {
            setRefreshNonce((value) => value + 1);
            return true;
        };

        return () => {
            if (window.__spaNavigate === spaNavigate) delete window.__spaNavigate;
            if (window.__spaCommitBootstrap === spaCommitBootstrap) delete window.__spaCommitBootstrap;
            delete window.__spaRefresh;
        };
    }, [navigate, refreshNonce, surface]);

    useEffect(() => {
        if (pageState.requestKey === requestKey) {
            setLoading(false);
            syncLegacyNavigationState(surface, location.pathname);
            return undefined;
        }

        const controller = new AbortController();
        setLoading(true);

        fetch(targetUrl.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-React-Navigation': '1',
            },
            signal: controller.signal,
        })
            .then(async (response) => {
                const contentType = response.headers.get('content-type') || '';
                if (!response.ok || !contentType.includes('application/json')) {
                    throw new Error(`SPA navigation returned HTTP ${response.status}`);
                }

                return response.json();
            })
            .then((nextBootstrap) => {
                if (!nextBootstrap?.page || !nextBootstrap?.props || nextBootstrap.surface !== surface) {
                    throw new Error('SPA navigation returned an incompatible page payload.');
                }

                setPageState({ bootstrap: nextBootstrap, requestKey });
                lastLoadedUrlRef.current = urlKey;
                if (nextBootstrap.title) document.title = nextBootstrap.title;
                syncLegacyNavigationState(surface, targetUrl.pathname);
            })
            .catch((error) => {
                if (error.name === 'AbortError') return;
                console.error('SPA navigation failed.', error);
                if (surface === 'admin') {
                    setLoading(false);
                    navigate(lastLoadedUrlRef.current, { replace: true });
                    if (typeof window.notification === 'function') {
                        window.notification('error', 'Không thể tải trang quản trị. Vui lòng thử lại.', 'Lỗi điều hướng');
                    }
                    return;
                }
                window.location.assign(targetUrl.toString());
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [location.pathname, navigate, pageState.requestKey, requestKey, surface, targetUrl, urlKey]);

    if (loading || pageState.requestKey !== requestKey) {
        return (
            <div style={{ minHeight: 260, display: 'grid', placeItems: 'center' }} aria-live="polite" aria-busy="true">
                <Spin size="large" />
            </div>
        );
    }

    return <AppRoutes bootstrap={pageState.bootstrap} />;
}
