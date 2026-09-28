import lottie from 'lottie-web';

function setupUserLoadingAnimation() {
    const container = document.querySelector('[data-loading-animation]');

    if (!container || container.dataset.lottieInitialized === 'true') return;

    const animationPath = container.dataset.animationPath;
    if (!animationPath) return;

    lottie.loadAnimation({
        container,
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: animationPath,
        rendererSettings: {
            preserveAspectRatio: 'xMidYMid meet',
        },
    });

    container.dataset.lottieInitialized = 'true';
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupUserLoadingAnimation, { once: true });
} else {
    setupUserLoadingAnimation();
}
