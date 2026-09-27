import { Outlet } from 'react-router-dom';
import { Alert } from 'antd';

export default function AdminLayout({ flash }) {
    return (
        <>
            {(flash?.success || flash?.error || flash?.warning) && (
                <div className="container-fluid px-4 pt-3">
                    {flash?.success && <Alert type="success" showIcon message={flash.success} closable />}
                    {flash?.error && <Alert type="error" showIcon message={flash.error} closable style={{ marginTop: flash?.success ? 12 : 0 }} />}
                    {flash?.warning && (
                        <Alert
                            type="warning"
                            showIcon
                            message={flash.warning}
                            closable
                            style={{ marginTop: flash?.success || flash?.error ? 12 : 0 }}
                        />
                    )}
                </div>
            )}
            <Outlet />
        </>
    );
}
