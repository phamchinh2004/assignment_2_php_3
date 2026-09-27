import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import viVN from 'antd/locale/vi_VN';
import App from './App';
import { readPageBootstrap } from './bootstrap';

const rootElement = document.getElementById('react-app-root');

if (rootElement) {
    try {
        const bootstrap = readPageBootstrap();

        createRoot(rootElement).render(
            <React.StrictMode>
                <ConfigProvider locale={viVN} theme={{ token: { borderRadius: 10 } }}>
                    <BrowserRouter>
                        <App bootstrap={bootstrap} />
                    </BrowserRouter>
                </ConfigProvider>
            </React.StrictMode>
        );
    } catch (exception) {
        console.error(exception);
        rootElement.innerHTML = '<div class="alert alert-danger">Không thể khởi tạo ứng dụng React.</div>';
    }
}
