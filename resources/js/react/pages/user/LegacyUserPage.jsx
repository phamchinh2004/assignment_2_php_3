import { useEffect } from 'react';
import '../../../../css/user/home.css';
import '../../../../css/user/lucky-wheel.css';
import '../../../../css/user/lucky-wheel-rewards.css';
import '../../../../css/user/lucky-wheel-reward-history.css';
import '../../../../css/user/distribution.css';
import '../../../../css/user/bank-account.css';
import '../../../../css/user/personal_information.css';
import '../../../../css/user/withdraw_money.css';
import '../../../../css/user/order.css';
import '../../../../css/user/order_detail.css';

const pageLoaders = {
    home: async () => {
        await Promise.all([
            import('../../../user/home.js'),
            import('../../../user/lucky-wheel.js'),
        ]);
        return [window.__initHomePage?.(), window.__initLuckyWheel?.()];
    },
    distribution: async () => {
        await import('../../../user/distribution.js');
        return [window.__initDistributionPage?.()];
    },
    personal_information: async () => {
        await Promise.all([
            import('../../../user/personal_information.js'),
            import('../../../user/bank-account.js'),
        ]);
        return [window.__initPersonalInformationPage?.(), window.__initBankAccounts?.()];
    },
    withdraw_money: async () => {
        await import('../../../user/withdraw_money.js');
        return [window.__initWithdrawPage?.()];
    },
    order_detail: async () => {
        await import('../../../user/order_detail.js');
        return [window.__initOrderDetailPage?.()];
    },
};

export default function LegacyUserPage({ config }) {
    const legacyPage = config.legacyPage;
    const html = config.html || '';

    useEffect(() => {
        let cancelled = false;
        let cleanups = [];
        const previousGlobals = new Map();

        Object.entries(config.globals || {}).forEach(([key, value]) => {
            previousGlobals.set(key, {
                existed: Object.prototype.hasOwnProperty.call(window, key),
                value: window[key],
            });
            window[key] = value;
        });

        const loader = pageLoaders[legacyPage];
        if (loader) {
            loader().then((results = []) => {
                if (cancelled) {
                    results.filter((item) => typeof item === 'function').forEach((cleanup) => cleanup());
                    return;
                }
                cleanups = results.filter((item) => typeof item === 'function');
            }).catch((error) => {
                console.error(`Unable to initialize legacy user page: ${legacyPage}`, error);
            });
        }

        return () => {
            cancelled = true;
            cleanups.reverse().forEach((cleanup) => cleanup());
            previousGlobals.forEach((entry, key) => {
                if (entry.existed) window[key] = entry.value;
                else delete window[key];
            });
        };
    }, [config.globals, legacyPage]);

    return <div data-legacy-user-route={legacyPage} dangerouslySetInnerHTML={{ __html: html }} />;
}
