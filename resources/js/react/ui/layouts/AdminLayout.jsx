import { useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import '../../../../css/admin/react-admin.css';

export default function AdminLayout() {
    const location = useLocation();
    const isChatPage = location.pathname === '/admin/chat-panel';

    useEffect(() => {
        document.body.classList.toggle('admin-react-chat-active', isChatPage);

        return () => document.body.classList.remove('admin-react-chat-active');
    }, [isChatPage]);

    return (
        <div className={`admin-react-shell${isChatPage ? ' admin-react-shell--chat' : ''}`}>
            <main className="admin-react-content">
                <Outlet />
            </main>
        </div>
    );
}
