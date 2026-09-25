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
    <mq-nav-toggle controls="nav-links"></mq-nav-toggle>

*/

const escapeHtml = (value = "") =>
    String(value).replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
    })[char]);

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

/* Big number with dashed line, heading and text */
class MqStat extends MqElement {
    render(content) {
        return `
            <p class="stat-value dashed-line">${this.attr("value")}</p>
            <p class="stat-heading">${this.attr("heading")}</p>
            <p class="stat-text">${content}</p>
        `;
    }
}

/* Accordion entry. Number is generated from the position unless `number` is set.
   All items with the same `group` (default "faq") are exclusive: only one is open. */
const FAQ_ICON = "imgs/accordionPlus.svg";

class MqFaqItem extends MqElement {
    render(content) {
        const siblings = [...this.parentElement.children].filter((el) => el.localName === this.localName);
        const number = this.getAttribute("number") ?? String(siblings.indexOf(this) + 1).padStart(2, "0");
        const open = this.hasAttribute("open") ? " open" : "";

        return `
            <details class="faq-item" name="${this.attr("group", "faq")}"${open}>
                <summary class="faq-summary">
                    <span class="faq-number">${escapeHtml(number)}</span>
                    <span class="faq-question">${this.attr("heading")}</span>
                    <img class="faq-icon" src="${this.attr("icon", FAQ_ICON)}" alt="">
                </summary>
                <div class="faq-answer">${content}</div>
            </details>
        `;
    }
}

/* Team card: name, image, role */
class MqTeamMember extends MqElement {
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
