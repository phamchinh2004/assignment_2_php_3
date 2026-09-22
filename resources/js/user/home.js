document.addEventListener("DOMContentLoaded", () => {
    const root = document.querySelector("[data-home-page]");
    if (!root) return;

    const config = window.homePageConfig || {};
    const messages = config.messages || {};
    const decorativeSocialProof = config.decorativeSocialProof || {};

    const announcement = root.querySelector("[data-home-announcement]");
    if (announcement) {
        const closeButton = announcement.querySelector(
            "[data-home-announcement-close]",
        );

        closeButton?.addEventListener("click", () => {
            announcement.classList.add("is-hiding");
            window.setTimeout(() => {
                announcement.hidden = true;
                announcement.classList.remove("is-hiding");
            }, 180);
        });
    }

    const depositButton = root.querySelector("#btn_nap_tien");
    depositButton?.addEventListener("click", () => {
        window.AppDialog?.alert({
            icon: "warning",
            title: messages.depositUnavailableTitle || "Thông báo",
            text: messages.depositUnavailableText || "",
            confirmText: "Đóng",
        });
    });

    const tabButtons = [
        ...root.querySelectorAll(".home-info-tab[data-content-target]"),
    ];
    const tabPanels = [...root.querySelectorAll(".home-info-panel")];

    const activateInfoPanel = (button) => {
        const targetId = button.dataset.contentTarget;
        const targetPanel = root.querySelector(`#${CSS.escape(targetId)}`);
        if (!targetPanel) return;

        tabButtons.forEach((item) => {
            const isActive = item === button;
            item.classList.toggle("is-active", isActive);
            item.setAttribute("aria-selected", String(isActive));
        });

        tabPanels.forEach((panel) => {
            const isActive = panel === targetPanel;
            panel.classList.toggle("is-active", isActive);
            panel.hidden = !isActive;
        });
    };

    tabButtons.forEach((button) => {
        button.addEventListener("click", () => activateInfoPanel(button));
    });

    const activityStream = root.querySelector("[data-social-activity]");
    if (activityStream) {
        const samplePhonePrefixes = [
            "032",
            "033",
            "034",
            "035",
            "036",
            "037",
            "038",
            "039",
            "070",
            "076",
            "077",
            "078",
            "079",
            "081",
            "082",
            "083",
            "084",
            "085",
            "088",
            "086",
            "089",
            "090",
            "091",
            "092",
            "093",
            "094",
            "096",
            "097",
            "098",
            "099",
            "052",
            "056",
            "058",
            "059",
        ];
        const sampleAmounts = [
            0.18347, 0.7291, 1.28456, 2.9134, 4.06782, 5.79123, 7.3189, 9.64271,
            11.2843, 13.90754, 16.5321, 19.84627, 22.13845, 25.9731, 29.40567,
            33.7182, 38.26194, 42.90513, 47.31876, 53.6721, 59.18453, 64.9287,
            71.50346, 78.2619, 84.93752, 92.3184, 99.76431, 108.4527, 117.98354,
            127.6412, 138.29567, 149.0834, 161.72895, 174.3821, 188.95734,
            203.6418, 219.30572, 236.9184, 254.63791, 273.1845, 292.76318,
            314.5072, 337.91846, 361.2047, 386.75219, 412.6385, 439.18764,
            467.9032, 498.27153, 529.6841, 563.91827, 598.4726, 635.18394,
            673.9281, 714.36572, 756.9184, 802.47319, 849.6213, 898.37465,
            949.1832, 1003.72841, 1061.3957, 1122.68453, 1187.2396, 1254.87319,
            1326.4185, 1401.93726, 1481.6834, 1565.29471, 1653.9182, 1746.38257,
            1843.7291, 1946.18364, 2053.7189, 2167.39452, 2286.9183, 2412.63718,
            2545.1847, 2684.72953, 2831.3946, 2985.61728, 3147.2839, 3317.69451,
            3496.1284, 3683.75291, 3881.3947, 4088.61753, 4306.1842, 4534.72981,
            4774.3186, 5025.94713,
        ];
        let activityInterval = null;

        const pickSample = (items) =>
            items[Math.floor(Math.random() * items.length)];
        const makeSamplePhone = () =>
            `${pickSample(samplePhonePrefixes)}**${Math.floor(100 + Math.random() * 900)}`;
        const makeSampleAmount = () => `$${pickSample(sampleAmounts)}`;
        const makeSampleTime = () => {
            const seconds = Math.floor(Math.random() * 60);
            if (seconds < 1) return decorativeSocialProof.justNow || "Vừa xong";
            return `${Math.max(1, seconds)} ${decorativeSocialProof.secondsAgo || "giây trước"}`;
        };

        const buildSampleActivity = (animate = true) => {
            const item = document.createElement("article");
            item.className = `home-activity-item${animate ? " is-entering" : ""}`;

            const icon = document.createElement("span");
            icon.className = "home-activity-item__icon";
            icon.innerHTML =
                '<i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i>';

            const identity = document.createElement("div");
            identity.className = "home-activity-item__identity";
            const phone = document.createElement("strong");
            phone.textContent = makeSamplePhone();
            const label = document.createElement("small");
            label.textContent = "Hoạt động hôm nay";
            identity.append(phone, label);

            const value = document.createElement("div");
            value.className = "home-activity-item__value";
            const amount = document.createElement("strong");
            amount.textContent = makeSampleAmount();
            const time = document.createElement("small");
            time.textContent = makeSampleTime();
            value.append(amount, time);

            item.append(icon, identity, value);
            if (animate)
                requestAnimationFrame(() =>
                    item.classList.remove("is-entering"),
                );
            return item;
        };

        const seedActivity = () => {
            const fragment = document.createDocumentFragment();
            for (let index = 0; index < 4; index += 1)
                fragment.append(buildSampleActivity(false));
            activityStream.replaceChildren(fragment);
        };

        const pushActivity = () => {
            if (document.hidden) return;
            activityStream.prepend(buildSampleActivity());
            while (activityStream.children.length > 4)
                activityStream.lastElementChild?.remove();
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
        document.addEventListener("visibilitychange", () =>
            document.hidden ? stopActivity() : startActivity(),
        );
        window.addEventListener("pagehide", stopActivity, { once: true });
    }

    const testimonials = root.querySelector("[data-testimonials]");
    if (testimonials) {
        const slides = [
            ...testimonials.querySelectorAll("[data-testimonial-slide]"),
        ];
        const dots = [
            ...testimonials.querySelectorAll("[data-testimonial-dot]"),
        ];
        const previousButton = testimonials.querySelector(
            "[data-testimonial-prev]",
        );
        const nextButton = testimonials.querySelector(
            "[data-testimonial-next]",
        );
        const stage = testimonials.querySelector("[data-testimonial-stage]");
        let currentIndex = 0;
        let testimonialInterval = null;
        let touchStartX = 0;

        const showTestimonial = (index) => {
            if (!slides.length) return;
            currentIndex = (index + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === currentIndex;
                slide.classList.toggle("is-active", isActive);
                slide.setAttribute("aria-hidden", String(!isActive));
            });

            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === currentIndex;
                dot.classList.toggle("is-active", isActive);
                dot.setAttribute("aria-current", isActive ? "true" : "false");
            });
        };

        const stopTestimonials = () => {
            if (!testimonialInterval) return;
            window.clearInterval(testimonialInterval);
            testimonialInterval = null;
        };

        const startTestimonials = () => {
            if (testimonialInterval || document.hidden || slides.length < 2)
                return;
            testimonialInterval = window.setInterval(
                () => showTestimonial(currentIndex + 1),
                5000,
            );
        };

        const restartTestimonials = () => {
            stopTestimonials();
            startTestimonials();
        };

        previousButton?.addEventListener("click", () => {
            showTestimonial(currentIndex - 1);
            restartTestimonials();
        });

        nextButton?.addEventListener("click", () => {
            showTestimonial(currentIndex + 1);
            restartTestimonials();
        });

        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                showTestimonial(Number(dot.dataset.testimonialDot || 0));
                restartTestimonials();
            });
        });

        testimonials.addEventListener("mouseenter", stopTestimonials);
        testimonials.addEventListener("mouseleave", startTestimonials);
        testimonials.addEventListener("focusin", stopTestimonials);
        testimonials.addEventListener("focusout", (event) => {
            if (!testimonials.contains(event.relatedTarget))
                startTestimonials();
        });

        stage?.addEventListener(
            "touchstart",
            (event) => {
                touchStartX = event.touches[0]?.clientX || 0;
            },
            { passive: true },
        );

        stage?.addEventListener(
            "touchend",
            (event) => {
                const touchEndX = event.changedTouches[0]?.clientX || 0;
                const distance = touchStartX - touchEndX;
                if (Math.abs(distance) < 45) return;
                showTestimonial(currentIndex + (distance > 0 ? 1 : -1));
                restartTestimonials();
            },
            { passive: true },
        );

        document.addEventListener("visibilitychange", () =>
            document.hidden ? stopTestimonials() : startTestimonials(),
        );
        window.addEventListener("pagehide", stopTestimonials, { once: true });

        showTestimonial(0);
        startTestimonials();
    }
});
