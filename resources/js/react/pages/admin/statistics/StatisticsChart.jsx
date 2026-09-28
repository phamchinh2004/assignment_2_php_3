import { useEffect, useRef } from 'react';
import { Alert } from 'antd';

export default function StatisticsChart({ config, height = 300, ariaLabel = 'Biểu đồ thống kê' }) {
    const canvasRef = useRef(null);
    const chartRef = useRef(null);

    useEffect(() => {
        if (!window.Chart || !canvasRef.current || !config) return undefined;

        chartRef.current?.destroy();
        chartRef.current = new window.Chart(canvasRef.current, config);

        return () => {
            chartRef.current?.destroy();
            chartRef.current = null;
        };
    }, [config]);

    if (!window.Chart) {
        return <Alert type="warning" showIcon message="Thư viện biểu đồ chưa được tải." />;
    }

    return (
        <div style={{ position: 'relative', height }}>
            <canvas ref={canvasRef} role="img" aria-label={ariaLabel} />
        </div>
    );
}
