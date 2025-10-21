// Balance Fluctuation Chart
let balanceChart = null;
let fullChartData = [];

document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra nếu có dữ liệu chart
    if (typeof window.chartData !== 'undefined' && window.chartData.length > 0) {
        fullChartData = window.chartData;
        initializeChart(fullChartData);
    }
    
    // Time filter buttons
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const period = this.getAttribute('data-period');
            filterChartData(period);
        });
    });
});

function initializeChart(data) {
    if (data.length === 0) {
        document.getElementById('balanceChart').innerHTML = `
            <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                <i class="fa-solid fa-chart-line fa-3x mb-3" style="color: #d1d5db;"></i>
                <p style="color: #6b7280;">Chưa có dữ liệu để hiển thị biểu đồ</p>
            </div>
        `;
        return;
    }
    
    // Chuẩn bị dữ liệu cho ApexCharts
    const chartSeries = data.map(item => ({
        x: new Date(item.date).getTime(),
        y: parseFloat(item.balance)
    }));
    
    // Tính toán min/max để đặt range cho trục Y
    const balances = data.map(item => parseFloat(item.balance));
    const minBalance = Math.min(...balances);
    const maxBalance = Math.max(...balances);
    const padding = (maxBalance - minBalance) * 0.1 || 10;
    
    // Xác định màu sắc dựa trên xu hướng
    const isPositiveTrend = balances[balances.length - 1] >= balances[0];
    const lineColor = isPositiveTrend ? '#10b981' : '#ef4444';
    const gradientColor = isPositiveTrend ? 
        ['#10b981', 'rgba(16, 185, 129, 0.1)'] : 
        ['#ef4444', 'rgba(239, 68, 68, 0.1)'];
    
    const options = {
        series: [{
            name: 'Số dư',
            data: chartSeries
        }],
        chart: {
            type: 'area',
            height: 350,
            fontFamily: 'inherit',
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true,
                    reset: true
                },
                autoSelected: 'zoom'
            },
            zoom: {
                enabled: true,
                type: 'x',
                autoScaleYaxis: true
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3,
            colors: [lineColor]
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.05,
                stops: [0, 100],
                colorStops: [
                    {
                        offset: 0,
                        color: gradientColor[0],
                        opacity: 0.4
                    },
                    {
                        offset: 100,
                        color: gradientColor[1],
                        opacity: 0.05
                    }
                ]
            }
        },
        grid: {
            show: true,
            borderColor: '#f3f4f6',
            strokeDashArray: 4,
            position: 'back',
            xaxis: {
                lines: {
                    show: false
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            },
            padding: {
                top: 0,
                right: 20,
                bottom: 0,
                left: 10
            }
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: {
                    colors: '#6b7280',
                    fontSize: '11px',
                    fontWeight: 500
                },
                datetimeFormatter: {
                    year: 'yyyy',
                    month: 'MMM yyyy',
                    day: 'dd MMM',
                    hour: 'HH:mm'
                }
            },
            axisBorder: {
                show: true,
                color: '#e5e7eb'
            },
            axisTicks: {
                show: true,
                color: '#e5e7eb'
            }
        },
        yaxis: {
            min: minBalance - padding,
            max: maxBalance + padding,
            labels: {
                style: {
                    colors: '#6b7280',
                    fontSize: '11px',
                    fontWeight: 500
                },
                formatter: function(value) {
                    return '$' + value.toFixed(2);
                }
            }
        },
        tooltip: {
            enabled: true,
            theme: 'light',
            style: {
                fontSize: '12px',
                fontFamily: 'inherit'
            },
            x: {
                format: 'dd/MM/yyyy HH:mm'
            },
            y: {
                formatter: function(value) {
                    return '$' + value.toFixed(2);
                }
            },
            marker: {
                show: true
            },
            custom: function({series, seriesIndex, dataPointIndex, w}) {
                const value = series[seriesIndex][dataPointIndex];
                const date = new Date(w.config.series[seriesIndex].data[dataPointIndex].x);
                const formattedDate = formatDate(date);
                
                // Xác định loại giao dịch từ dữ liệu gốc
                const transType = fullChartData[dataPointIndex]?.type || '';
                let transTypeText = '';
                let transTypeColor = '#6b7280';
                
                if (transType === 'deposit') {
                    transTypeText = '💰 Nạp tiền';
                    transTypeColor = '#3b82f6';
                } else if (transType === 'withdraw') {
                    transTypeText = '💸 Rút tiền';
                    transTypeColor = '#f59e0b';
                } else if (transType === 'profit') {
                    transTypeText = '📈 Lợi nhuận';
                    transTypeColor = '#10b981';
                } else if (transType === 'order') {
                    transTypeText = '📦 Đặt hàng';
                    transTypeColor = '#ef4444';
                } else if (transType === 'penalty') {
                    transTypeText = '⚠️ Phạt';
                    transTypeColor = '#f59e0b';
                }
                
                return `
                    <div style="background: white; padding: 12px 16px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 1px solid #e5e7eb;">
                        <div style="font-size: 11px; color: #9ca3af; margin-bottom: 6px; font-weight: 500;">
                            ${formattedDate}
                        </div>
                        ${transTypeText ? `
                            <div style="font-size: 12px; color: ${transTypeColor}; margin-bottom: 8px; font-weight: 600;">
                                ${transTypeText}
                            </div>
                        ` : ''}
                        <div style="font-size: 16px; font-weight: 700; color: #1f2937;">
                            $${value.toFixed(2)}
                        </div>
                        <div style="font-size: 10px; color: #6b7280; margin-top: 4px;">
                            Số dư tại thời điểm này
                        </div>
                    </div>
                `;
            }
        },
        markers: {
            size: 0,
            colors: [lineColor],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
                size: 6,
                sizeOffset: 3
            }
        },
        legend: {
            show: false
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 250,
                    toolbar: {
                        show: false
                    }
                },
                grid: {
                    padding: {
                        right: 10,
                        left: 0
                    }
                },
                xaxis: {
                    labels: {
                        style: {
                            fontSize: '10px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '10px'
                        }
                    }
                }
            }
        }]
    };
    
    // Render chart
    if (balanceChart) {
        balanceChart.destroy();
    }
    
    balanceChart = new ApexCharts(document.querySelector("#balanceChart"), options);
    balanceChart.render();
}

function filterChartData(period) {
    if (!fullChartData || fullChartData.length === 0) return;
    
    let filteredData = fullChartData;
    
    if (period !== 'all') {
        const days = parseInt(period);
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - days);
        
        filteredData = fullChartData.filter(item => {
            const itemDate = new Date(item.date);
            return itemDate >= cutoffDate;
        });
    }
    
    // Re-initialize chart with filtered data
    if (filteredData.length > 0) {
        initializeChart(filteredData);
    } else {
        document.getElementById('balanceChart').innerHTML = `
            <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                <i class="fa-solid fa-chart-line fa-3x mb-3" style="color: #d1d5db;"></i>
                <p style="color: #6b7280;">Không có dữ liệu trong khoảng thời gian này</p>
            </div>
        `;
    }
}

function formatDate(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}
