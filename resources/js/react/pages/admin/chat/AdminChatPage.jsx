import { useEffect, useRef } from 'react';
import '../../../../../css/admin/chat/chat-component.css';
import '../../../../../css/admin/sidebar-chat.css';
import '../../../../../css/admin/chat.css';

function executeInlineScripts(container) {
    if (window.__adminChatInlineScriptsExecuted) return false;

    container.querySelectorAll('script:not([src])').forEach((source) => {
        const script = document.createElement('script');
        script.textContent = source.textContent || '';
        document.head.appendChild(script);
        script.remove();
    });

    window.__adminChatInlineScriptsExecuted = true;
    return true;
}

function waitForLivewireComponent(container, callback) {
    let frame = 0;
    let cancelled = false;

    const check = () => {
        if (cancelled || !container.isConnected) return;

        const root = container.querySelector('#chat-root[wire\\:id]');
        const wireId = root?.getAttribute('wire:id');
        if (wireId && window.Livewire?.find?.(wireId)) {
            callback();
            return;
        }

        frame = window.requestAnimationFrame(check);
    };

    frame = window.requestAnimationFrame(check);
    return () => {
        cancelled = true;
        if (frame) window.cancelAnimationFrame(frame);
    };
}

export default function AdminChatPage({ config }) {
    const containerRef = useRef(null);

    useEffect(() => {
        const container = containerRef.current;
        if (!container) return undefined;

        let cancelled = false;
        let stopWaiting = () => {};

        Promise.all([
            import('../../../../admin/chat.js'),
            import('../../../../admin/chat/chat-panel.js'),
        ]).then(() => {
            if (cancelled) return;

            stopWaiting = waitForLivewireComponent(container, () => {
                if (cancelled) return;
                const initializedNow = executeInlineScripts(container);
                if (initializedNow) {
                    document.dispatchEvent(new CustomEvent('livewire:initialized'));
                }
            });
        }).catch((error) => {
            console.error('Unable to initialize admin chat page.', error);
        });

        return () => {
            cancelled = true;
            stopWaiting();
        };
    }, [config.html]);

    return (
        <div
            ref={containerRef}
            className="admin-chat-page"
            data-admin-chat-page
            dangerouslySetInnerHTML={{ __html: config.html || '' }}
        />
    );
}
