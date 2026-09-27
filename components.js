/*

Web Components (Custom Elements) for MainQuest.

No Shadow DOM: the rendered markup lives in the normal DOM,
so design.css and styles.css style it like any other HTML.

Each component renders once from its attributes + inner content.

    <mq-label variant="light">Text</mq-label>
    <mq-marquee duration="30s" copies="2"> logos </mq-marquee>
    <mq-carousel start="center"> images </mq-carousel>
    <mq-stat value="30+" heading="Title"> text </mq-stat>
    <mq-faq-item heading="Question" open> answer </mq-faq-item>
    <mq-team-member name="Name" role="Role" img="path.png"></mq-team-member>
    <mq-nav></mq-nav>   (links: NAV_LINKS below)
    <mq-nav-toggle controls="nav-links"></mq-nav-toggle>
    <mq-event-card img date location category heading link link-label> text </mq-event-card>
    <mq-event-filter for="grid-id"></mq-event-filter>

*/

const escapeHtml = (value = "") =>
    String(value).replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
    })[char]);

const prefersReducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;

/* Calls callback once, as soon as the element scrolls into view */
const onceVisible = (element, callback, threshold = 0.3) => {
    const observer = new IntersectionObserver((entries) => {
        if (!entries[0].isIntersecting) return;
        observer.disconnect();
        callback();
    }, { threshold });
    observer.observe(element);
};

/* Position of an element among its siblings of the same tag (0-based) */
const siblingIndex = (element) =>
    [...element.parentElement.children]
        .filter((el) => el.localName === element.localName)
        .indexOf(element);

/* Base class: renders exactly once, passes the original inner HTML to render() */
class MqElement extends HTMLElement {
    #rendered = false;

    connectedCallback() {
        if (this.#rendered) return;
        this.#rendered = true;
        this.innerHTML = this.render(this.innerHTML.trim());
        this.afterRender?.();
    }

    attr(name, fallback = "") {
        return escapeHtml(this.getAttribute(name) ?? fallback);
    }
}

/* Exclamation icon + text. variant="light" = white icon for dark backgrounds */
const LABEL_ICONS = {
    dark: "imgs/exclamationBrown.png",
    light: "imgs/exclamationWhite.png",
};

class MqLabel extends MqElement {
    render(content) {
        const variant = this.getAttribute("variant") ?? "dark";
        const icon = this.getAttribute("icon") ?? LABEL_ICONS[variant] ?? LABEL_ICONS.dark;

        return `
            <img class="label-icon" src="${escapeHtml(icon)}" alt="">
            <span class="label-text">${content}</span>
        `;
    }
}

/* Endless scrolling row. Content is repeated `copies` times, the copies are hidden for screen readers */
class MqMarquee extends MqElement {
    render(content) {
        const duration = this.getAttribute("duration");
        if (duration) this.style.setProperty("--marquee-duration", duration);

        const copies = Math.max(2, Number(this.getAttribute("copies")) || 2);

        return Array.from({ length: copies }, (_, index) => `
            <div class="marquee-group"${index > 0 ? ' aria-hidden="true"' : ""}>${content}</div>
        `).join("");
    }
}

/* Horizontal scroller: native scroll for touch/trackpad + drag with the mouse.
   start="center" scrolls to the middle initially, so both edges are cut off. */
class MqCarousel extends MqElement {
    render(content) {
        return content;
    }

    afterRender() {
        if (!this.hasAttribute("tabindex")) this.tabIndex = 0;

        if (this.getAttribute("start") === "center") {
            this.scrollLeft = (this.scrollWidth - this.clientWidth) / 2;
        }

        this.#enableMouseDrag();
    }

    #enableMouseDrag() {
        let startX = 0;
        let startScroll = 0;
        let moved = false;

        this.addEventListener("pointerdown", (event) => {
            if (event.pointerType !== "mouse" || event.button !== 0) return;
            startX = event.clientX;
            startScroll = this.scrollLeft;
            moved = false;
            this.setPointerCapture(event.pointerId);
            this.classList.add("is-dragging");
        });

        this.addEventListener("pointermove", (event) => {
            if (!this.classList.contains("is-dragging")) return;
            const distance = event.clientX - startX;
            if (Math.abs(distance) > 3) moved = true;
            this.scrollLeft = startScroll - distance;
        });

        const stopDrag = () => this.classList.remove("is-dragging");
        this.addEventListener("pointerup", stopDrag);
        this.addEventListener("pointercancel", stopDrag);

        // no native image dragging, no click after a drag
        this.addEventListener("dragstart", (event) => event.preventDefault());
        this.addEventListener("click", (event) => {
            if (!moved) return;
            event.preventDefault();
            event.stopPropagation();
            moved = false;
        }, true);
    }
}

/* Big number with dashed line, heading and text.
   When scrolled into view, the first number in `value` counts up (e.g. "16,4M Leute" -> 0,0 … 16,4)
   and the dashed line draws itself. `no-count` disables counting, `duration` sets the time in ms. */
class MqStat extends MqElement {
    #count = null;

    render(content) {
        const value = this.getAttribute("value") ?? "";
        const counts = !this.hasAttribute("no-count") && !prefersReducedMotion();
        this.#count = counts ? parseCount(value) : null;

        return `
            <p class="stat-value dashed-line">
                <span class="visually-hidden">${escapeHtml(value)}</span>
                <span class="stat-number" aria-hidden="true">${escapeHtml(this.#count ? this.#count.format(0) : value)}</span>
            </p>
            <p class="stat-heading">${this.attr("heading")}</p>
            <p class="stat-text">${content}</p>
        `;
    }

    afterRender() {
        onceVisible(this, () => {
            this.classList.add("is-visible");
            if (this.#count) this.#countUp();
        }, 0.5);
    }

    #countUp() {
        const output = this.querySelector(".stat-number");
        const duration = Number(this.getAttribute("duration")) || 1500;
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out
            output.textContent = this.#count.format(this.#count.target * eased);
            if (progress < 1) requestAnimationFrame(tick);
        };

        requestAnimationFrame(tick);
    }
}

/* "16,4M Leute" -> { target: 16.4, format(n) -> "<n>M Leute" } (German decimal comma) */
const parseCount = (value) => {
    const match = value.match(/\d+(?:,\d+)?/);
    if (!match) return null;

    const prefix = value.slice(0, match.index);
    const suffix = value.slice(match.index + match[0].length);
    const decimals = match[0].split(",")[1]?.length ?? 0;
    const target = Number(match[0].replace(",", "."));

    const format = (number) => prefix + number.toLocaleString("de-DE", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
        useGrouping: false,
    }) + suffix;

    return { target, format };
};

/* Accordion entry. Number is generated from the position unless `number` is set.
   All items with the same `group` (default "faq") are exclusive: only one is open.
   The answer repeats number + heading, only shown in the desktop panel (styles.css). */
const FAQ_ICON = "imgs/accordionPlus.svg";

class MqFaqItem extends MqElement {
    render(content) {
        const number = this.getAttribute("number") ?? String(siblingIndex(this) + 1).padStart(2, "0");
        const open = this.hasAttribute("open") ? " open" : "";

        return `
            <details class="faq-item" name="${this.attr("group", "faq")}"${open}>
                <summary class="faq-summary">
                    <span class="faq-number">${escapeHtml(number)}</span>
                    <span class="faq-question">${this.attr("heading")}</span>
                    <img class="faq-icon" src="${this.attr("icon", FAQ_ICON)}" alt="">
                </summary>
                <div class="faq-answer">
                    <p class="faq-answer-heading" aria-hidden="true">
                        <span class="faq-answer-number">${escapeHtml(number)}</span>
                        ${this.attr("heading")}
                    </p>
                    <div class="faq-answer-text">${content}</div>
                </div>
            </details>
        `;
    }
}

/* Team card: name, image, role.
   Tilts into place when scrolled into view (--i = position, used for the stagger in CSS).
   On devices with a mouse the image tilts towards the cursor, `tilt` = max angle in deg. */
class MqTeamMember extends MqElement {
    afterRender() {
        this.style.setProperty("--i", siblingIndex(this));
        onceVisible(this, () => this.classList.add("is-visible"), 0.25);

        const canHover = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
        if (canHover && !prefersReducedMotion()) this.#enableHoverTilt();
    }

    #enableHoverTilt() {
        const image = this.querySelector(".team-image");
        const maxTilt = Number(this.getAttribute("tilt")) || 8;

        image.addEventListener("pointermove", (event) => {
            const rect = image.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width - 0.5;
            const y = (event.clientY - rect.top) / rect.height - 0.5;
            image.style.setProperty("--tilt-x", `${(-y * maxTilt).toFixed(2)}deg`);
            image.style.setProperty("--tilt-y", `${(x * maxTilt).toFixed(2)}deg`);
        });

        image.addEventListener("pointerleave", () => {
            image.style.removeProperty("--tilt-x");
            image.style.removeProperty("--tilt-y");
        });
    }

    render() {
        const name = this.getAttribute("name") ?? "";

        return `
            <div class="team-card">
                <h3 class="team-name">${escapeHtml(name)}</h3>
                <img class="team-image" src="${this.attr("img")}" alt="${this.attr("alt", `Portrait von ${name}`)}" loading="lazy">
                <p class="team-role">${this.attr("role")}</p>
            </div>
        `;
    }
}

/* Main navigation, shared by all pages -> edit the links only here.
   Links to sections of the current page become plain anchors (index.html#team -> #team),
   the link of the current page gets aria-current="page". */
const NAV_LINKS = [
    { label: "Next Quest", href: "index.html#whatIs" },
    { label: "Für Creator", href: "#" },
    { label: "Unsere Events", href: "events.html", badge: "!" },
    { label: "Für Brands", href: "#" },
    { label: "Über Uns", href: "index.html#team" },
];

class MqNav extends MqElement {
    render() {
        const currentPage = location.pathname.split("/").pop() || "index.html";
        const resolve = (href) => href.startsWith(`${currentPage}#`) ? href.slice(currentPage.length) : href;

        const links = NAV_LINKS.map(({ label, href, badge }) => {
            const current = href === currentPage ? ' aria-current="page"' : "";
            const badgeAttr = badge ? ` data-badge="${escapeHtml(badge)}"` : "";
            return `<li><a href="${escapeHtml(resolve(href))}"${current}${badgeAttr}>${escapeHtml(label)}</a></li>`;
        }).join("");

        return `
            <nav class="main-nav" data-sticky aria-label="Hauptnavigation">
                <a class="nav-logo" href="${currentPage === "index.html" ? "#hero" : "index.html"}">
                    <img src="imgs/wortlogo.png" alt="MainQuest" width="166" height="52">
                </a>
                <mq-nav-toggle controls="nav-links"></mq-nav-toggle>
                <ul class="nav-links" id="nav-links">${links}</ul>
            </nav>
        `;
    }
}

/* Event card: image, category, date + location, heading, short text, link.
   date as ISO (2025-10-31) -> shown as 31.10.2025. External links open in a new tab.
   Fades in when scrolled into view (--i = position, used for the stagger in CSS). */
class MqEventCard extends MqElement {
    render(content) {
        const heading = this.getAttribute("heading") ?? "";
        const date = this.getAttribute("date");
        const category = this.getAttribute("category");
        const link = this.getAttribute("link");
        const external = /^https?:\/\//.test(link ?? "");

        const meta = [
            date && `<time datetime="${escapeHtml(date)}">${formatDate(date)}</time>`,
            this.getAttribute("location") && this.attr("location"),
        ].filter(Boolean).join(" · ");

        return `
            <article class="event-card">
                <div class="event-media">
                    <img class="event-image" src="${this.attr("img")}" alt="${this.attr("alt")}" loading="lazy">
                    ${category ? `<span class="event-category">${escapeHtml(category)}</span>` : ""}
                </div>
                ${meta ? `<p class="event-meta">${meta}</p>` : ""}
                <h3 class="event-title">${escapeHtml(heading)}</h3>
                ${content ? `<p class="event-text">${content}</p>` : ""}
                ${link ? `
                    <a class="link-arrow link-arrow--small dashed-line" href="${escapeHtml(link)}"
                       ${external ? 'target="_blank" rel="noopener"' : ""}>
                        <span aria-hidden="true">→</span> ${this.attr("link-label", "Zum Recap")}
                        <span class="visually-hidden">: ${escapeHtml(heading)}</span>
                    </a>` : ""}
            </article>
        `;
    }

    afterRender() {
        this.style.setProperty("--i", siblingIndex(this));
        onceVisible(this, () => this.classList.add("is-visible"), 0.2);
    }
}

const formatDate = (iso) => new Date(iso).toLocaleDateString("de-DE", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    timeZone: "UTC",
});

/* Filter chips for event cards. The chips are generated from the `category`
   attributes of the cards inside the element with id `for`. */
class MqEventFilter extends MqElement {
    #grid = null;

    render() {
        this.#grid = document.getElementById(this.getAttribute("for"));
        const cards = [...this.#grid.querySelectorAll("mq-event-card[category]")];
        const categories = [...new Set(cards.map((card) => card.getAttribute("category")))];

        const chip = (label, value, pressed) =>
            `<button class="filter-chip" type="button" data-value="${escapeHtml(value)}" aria-pressed="${pressed}">${escapeHtml(label)}</button>`;

        return `
            <div class="filter-chips" role="group" aria-label="${this.attr("label", "Events filtern")}">
                ${chip(this.getAttribute("all-label") ?? "Alle", "", true)}
                ${categories.map((category) => chip(category, category, false)).join("")}
            </div>
        `;
    }

    afterRender() {
        this.addEventListener("click", (event) => {
            const selected = event.target.closest(".filter-chip");
            if (!selected) return;

            const value = selected.dataset.value;
            this.querySelectorAll(".filter-chip").forEach((chip) =>
                chip.setAttribute("aria-pressed", String(chip === selected)));
            this.#grid.querySelectorAll("mq-event-card").forEach((card) => {
                card.hidden = value !== "" && card.getAttribute("category") !== value;
            });
        });
    }
}

/* Burger button: toggles .is-open on the surrounding <nav> */
class MqNavToggle extends MqElement {
    render() {
        return `
            <button class="nav-toggle-button" type="button" aria-expanded="false"
                    aria-controls="${this.attr("controls")}" aria-label="${this.attr("label", "Menü")}">
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>
        `;
    }

    afterRender() {
        const nav = this.closest("nav");
        const button = this.querySelector("button");

        const setOpen = (open) => {
            nav.classList.toggle("is-open", open);
            button.setAttribute("aria-expanded", String(open));
        };

        button.addEventListener("click", () => setOpen(!nav.classList.contains("is-open")));
        nav.addEventListener("click", (event) => {
            if (event.target.closest("a")) setOpen(false);
        });
    }
}

customElements.define("mq-label", MqLabel);
customElements.define("mq-marquee", MqMarquee);
customElements.define("mq-carousel", MqCarousel);
customElements.define("mq-stat", MqStat);
customElements.define("mq-faq-item", MqFaqItem);
customElements.define("mq-team-member", MqTeamMember);
customElements.define("mq-nav-toggle", MqNavToggle);
customElements.define("mq-nav", MqNav);
customElements.define("mq-event-card", MqEventCard);
customElements.define("mq-event-filter", MqEventFilter);
