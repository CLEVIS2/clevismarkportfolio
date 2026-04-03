document.documentElement.classList.add("js");

const slides = Array.from(document.querySelectorAll(".slide"));
const dots = Array.from(document.querySelectorAll(".dot"));
const topbars = Array.from(document.querySelectorAll(".topbar"));

if (slides.length > 1) {
    let currentIndex = 0;

    const activateSlide = (index) => {
        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle("active", slideIndex === index);
        });

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle("active", dotIndex === index);
        });
    };

    const showNextSlide = () => {
        currentIndex = (currentIndex + 1) % slides.length;
        activateSlide(currentIndex);
    };

    window.setInterval(showNextSlide, 4500);
}

topbars.forEach((topbar) => {
    const toggle = topbar.querySelector(".menu-toggle");
    const nav = topbar.querySelector(".topnav");
    const overlay = topbar.querySelector(".nav-overlay");

    if (!toggle || !nav) {
        return;
    }

    const setOpen = (isOpen) => {
        topbar.classList.toggle("is-open", isOpen);
        toggle.setAttribute("aria-expanded", String(isOpen));
        document.documentElement.classList.toggle("nav-open", isOpen);
    };

    toggle.addEventListener("click", () => {
        setOpen(!topbar.classList.contains("is-open"));
    });

    nav.addEventListener("click", (event) => {
        if (event.target.closest("a, button")) {
            setOpen(false);
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 720) {
            setOpen(false);
        }
    });

    document.addEventListener("click", (event) => {
        if (!topbar.classList.contains("is-open")) {
            return;
        }

        if (!topbar.contains(event.target)) {
            setOpen(false);
        }
    });
    
    if (overlay) {
        overlay.addEventListener("click", () => {
            setOpen(false);
        });
    }

    window.addEventListener("scroll", () => {
        if (window.innerWidth <= 720 && topbar.classList.contains("is-open")) {
            setOpen(false);
        }
    }, { passive: true });

    window.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            setOpen(false);
        }
    });
});

const verificationPanel = document.querySelector("[data-verification-panel]");
const authForms = Array.from(document.querySelectorAll("[data-auth-form]"));

if (verificationPanel && authForms.length > 0) {
    const cardsContainer = verificationPanel.querySelector(".captcha-grid");
    const targetLabel = verificationPanel.querySelector("[data-verification-label]");
    const hiddenInputs = Array.from(document.querySelectorAll("[data-verification-input]"));
    const submitButtons = Array.from(document.querySelectorAll("[data-submit-button]"));
    const forms = Array.from(document.querySelectorAll("[data-auth-form]"));

    let locked = false;

    const setSubmitState = (enabled) => {
        submitButtons.forEach((button) => {
            button.disabled = !enabled;
        });
    };

    const updateTarget = (target, label) => {
        verificationPanel.dataset.verificationTarget = target;
        if (targetLabel) {
            targetLabel.textContent = label;
        }
    };

    const renderCards = (cards) => {
        cardsContainer.innerHTML = "";

        cards.forEach((card) => {
            const button = document.createElement("button");
            button.className = "captcha-card";
            button.type = "button";
            button.dataset.captchaCard = "";
            button.dataset.choice = card.key;

            const wrap = document.createElement("span");
            wrap.className = "captcha-image-wrap";

            const image = document.createElement("img");
            image.src = card.image;
            image.alt = `${card.label} image`;

            const feedback = document.createElement("span");
            feedback.className = "captcha-feedback";
            feedback.setAttribute("aria-hidden", "true");

            wrap.append(image, feedback);

            const label = document.createElement("span");
            label.className = "captcha-label";
            label.textContent = card.label;

            button.append(wrap, label);
            cardsContainer.appendChild(button);
        });
    };

    const hidePanel = () => {
        verificationPanel.classList.add("is-hidden");
        window.setTimeout(() => {
            verificationPanel.style.display = "none";
        }, 360);
    };

    const setLocked = (value) => {
        locked = value;
        cardsContainer.classList.toggle("is-busy", value);
    };

    cardsContainer.addEventListener("click", async (event) => {
        const card = event.target.closest("[data-captcha-card]");
        if (!card || locked) {
            return;
        }

        const choice = card.dataset.choice;
        setLocked(true);
        let shouldUnlockAfterRequest = true;

        try {
            const response = await fetch("verification_action.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: new URLSearchParams({ choice }),
            });

            const data = await response.json();

            if (!data.ok) {
                setLocked(false);
                return;
            }

            card.classList.remove("is-correct", "is-wrong");
            card.classList.add(data.correct ? "is-correct" : "is-wrong");

            const feedback = card.querySelector(".captcha-feedback");
            if (feedback) {
                feedback.textContent = data.correct ? "\u2713" : "\u00d7";
                feedback.classList.add("visible");
            }

            if (data.correct) {
                shouldUnlockAfterRequest = false;
                hiddenInputs.forEach((input) => {
                    input.value = choice;
                });

                setSubmitState(true);
                window.setTimeout(() => {
                    hidePanel();
                    setLocked(false);
                }, 450);
                return;
            }

            hiddenInputs.forEach((input) => {
                input.value = "";
            });

            setSubmitState(false);
            updateTarget(data.target, data.targetLabel);
            window.setTimeout(() => {
                renderCards(data.cards || []);
                setLocked(false);
            }, 260);
        } catch (error) {
            setLocked(false);
        } finally {
            if (shouldUnlockAfterRequest && !verificationPanel.classList.contains("is-hidden")) {
                if (locked) {
                    window.setTimeout(() => {
                        setLocked(false);
                    }, 320);
                }
            }
        }
    });

    forms.forEach((form) => {
        form.addEventListener("submit", () => {
            if (!verificationPanel.classList.contains("is-hidden")) {
                const enabled = hiddenInputs.some((input) => input.value !== "");
                setSubmitState(enabled);
            }
        });
    });

    setSubmitState(false);
}
