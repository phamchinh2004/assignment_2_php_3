
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-home-page]');
    if (!root) return;

    const config = window.homePageConfig || {};
    const messages = config.messages || {};
    const decorativeSocialProof = config.decorativeSocialProof || {};

    const announcement = root.querySelector('[data-home-announcement]');
    if (announcement) {
        const closeButton = announcement.querySelector('[data-home-announcement-close]');
        const announcementKey = announcement.dataset.announcementKey || 'default';
        const reappearAfter = Number(announcement.dataset.reappearAfter || 21600000);
        const storageKey = `home-announcement-dismissed:${announcementKey}`;

        const getDismissedAt = () => {
            try {
                return Number(window.localStorage.getItem(storageKey) || 0);
            } catch (error) {
                return 0;
            }
        };

        const saveDismissedAt = () => {
            try {
                window.localStorage.setItem(storageKey, String(Date.now()));
            } catch (error) {
                // Keep the close action working even when storage is unavailable.
            }
        };

        const dismissedAt = getDismissedAt();
        if (dismissedAt > 0 && Date.now() - dismissedAt < reappearAfter) {
            announcement.hidden = true;
        } else if (dismissedAt > 0) {
            try {
                window.localStorage.removeItem(storageKey);
            } catch (error) {
                // Storage cleanup is optional.
            }
        }

        closeButton?.addEventListener('click', () => {
            saveDismissedAt();
            announcement.classList.add('is-hiding');
            window.setTimeout(() => {
                announcement.hidden = true;
                announcement.classList.remove('is-hiding');
            }, 180);
        });
    }


    const depositButton = root.querySelector('#btn_nap_tien');
    depositButton?.addEventListener('click', () => {
        window.AppDialog?.alert({
            icon: 'warning',
            title: messages.depositUnavailableTitle || 'Thông báo',
            text: messages.depositUnavailableText || '',
            confirmText: 'Đóng',
        });
    });

    const tabButtons = [...root.querySelectorAll('.home-info-tab[data-content-target]')];
    const tabPanels = [...root.querySelectorAll('.home-info-panel')];

    const activateInfoPanel = (button) => {
        const targetId = button.dataset.contentTarget;
        const targetPanel = root.querySelector(`#${CSS.escape(targetId)}`);
        if (!targetPanel) return;

        tabButtons.forEach((item) => {
            const isActive = item === button;
            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-selected', String(isActive));
        });

        tabPanels.forEach((panel) => {
            const isActive = panel === targetPanel;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
        });
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => activateInfoPanel(button));
    });

    const activityStream = root.querySelector('[data-social-activity]');
    if (activityStream) {
        const samplePhonePrefixes = ['097', '098', '016', '093', '090', '091'];
        const sampleAmounts = [10, 25, 50, 75, 100, 150, 200, 250, 300, 400, 500, 750, 1000];
        let activityInterval = null;

        const pickSample = (items) => items[Math.floor(Math.random() * items.length)];
        const makeSamplePhone = () => `${pickSample(samplePhonePrefixes)}**${Math.floor(100 + Math.random() * 900)}`;
        const makeSampleAmount = () => `$${pickSample(sampleAmounts)}`;
        const makeSampleTime = () => {
            const seconds = Math.floor(Math.random() * 60);
            if (seconds < 1) return decorativeSocialProof.justNow || 'Vừa xong';
            return `${Math.max(1, seconds)} ${decorativeSocialProof.secondsAgo || 'giây trước'}`;
        };

        const buildSampleActivity = (animate = true) => {
            const item = document.createElement('article');
            item.className = `home-activity-item${animate ? ' is-entering' : ''}`;

            const icon = document.createElement('span');
            icon.className = 'home-activity-item__icon';
            icon.innerHTML = '<i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i>';

            const identity = document.createElement('div');
            identity.className = 'home-activity-item__identity';
            const phone = document.createElement('strong');
            phone.textContent = makeSamplePhone();
            const label = document.createElement('small');
            label.textContent = 'Hoạt động hôm nay';
            identity.append(phone, label);

            const value = document.createElement('div');
            value.className = 'home-activity-item__value';
            const amount = document.createElement('strong');
            amount.textContent = makeSampleAmount();
            const time = document.createElement('small');
            time.textContent = makeSampleTime();
            value.append(amount, time);

            item.append(icon, identity, value);
            if (animate) requestAnimationFrame(() => item.classList.remove('is-entering'));
            return item;
        };

        const seedActivity = () => {
            const fragment = document.createDocumentFragment();
            for (let index = 0; index < 4; index += 1) fragment.append(buildSampleActivity(false));
            activityStream.replaceChildren(fragment);
        };

        const pushActivity = () => {
            if (document.hidden) return;
            activityStream.prepend(buildSampleActivity());
            while (activityStream.children.length > 4) activityStream.lastElementChild?.remove();
        };

        const stopActivity = () => {
            if (!activityInterval) return;
            window.clearInterval(activityInterval);
            activityInterval = null;
        };

        const startActivity = () => {
            if (activityInterval || document.hidden) return;
            activityInterval = window.setInterval(pushActivity, 5000);
        };

        seedActivity();
        startActivity();
        document.addEventListener('visibilitychange', () => document.hidden ? stopActivity() : startActivity());
        window.addEventListener('pagehide', stopActivity, { once: true });
    }

    const testimonials = root.querySelector('[data-testimonials]');
    if (testimonials) {
        const slides = [...testimonials.querySelectorAll('[data-testimonial-slide]')];
        const dots = [...testimonials.querySelectorAll('[data-testimonial-dot]')];
        const previousButton = testimonials.querySelector('[data-testimonial-prev]');
        const nextButton = testimonials.querySelector('[data-testimonial-next]');
        const stage = testimonials.querySelector('[data-testimonial-stage]');
        let currentIndex = 0;
        let testimonialInterval = null;
        let touchStartX = 0;

        const showTestimonial = (index) => {
            if (!slides.length) return;
            currentIndex = (index + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === currentIndex;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', String(!isActive));
            });

            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === currentIndex;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-current', isActive ? 'true' : 'false');
            });
        };

        const stopTestimonials = () => {
            if (!testimonialInterval) return;
            window.clearInterval(testimonialInterval);
            testimonialInterval = null;
        };

        const startTestimonials = () => {
            if (testimonialInterval || document.hidden || slides.length < 2) return;
            testimonialInterval = window.setInterval(() => showTestimonial(currentIndex + 1), 5000);
        };

        const restartTestimonials = () => {
            stopTestimonials();
            startTestimonials();
        };

        previousButton?.addEventListener('click', () => {
            showTestimonial(currentIndex - 1);
            restartTestimonials();
        });

        nextButton?.addEventListener('click', () => {
            showTestimonial(currentIndex + 1);
            restartTestimonials();
        });

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                showTestimonial(Number(dot.dataset.testimonialDot || 0));
                restartTestimonials();
            });
        });

        testimonials.addEventListener('mouseenter', stopTestimonials);
        testimonials.addEventListener('mouseleave', startTestimonials);
        testimonials.addEventListener('focusin', stopTestimonials);
        testimonials.addEventListener('focusout', (event) => {
            if (!testimonials.contains(event.relatedTarget)) startTestimonials();
        });

        stage?.addEventListener('touchstart', (event) => {
            touchStartX = event.touches[0]?.clientX || 0;
        }, { passive: true });

        stage?.addEventListener('touchend', (event) => {
            const touchEndX = event.changedTouches[0]?.clientX || 0;
            const distance = touchStartX - touchEndX;
            if (Math.abs(distance) < 45) return;
            showTestimonial(currentIndex + (distance > 0 ? 1 : -1));
            restartTestimonials();
        }, { passive: true });

        document.addEventListener('visibilitychange', () => document.hidden ? stopTestimonials() : startTestimonials());
        window.addEventListener('pagehide', stopTestimonials, { once: true });

        showTestimonial(0);
        startTestimonials();
    }

});
