/*

Page-wide effects that are not components.
Activated via data attributes in index.html:

    <nav data-sticky>   -> nav sticks to the top, hides on scroll down, shows on scroll up

*/

/* Sticky navigation
   .is-scrolled -> page is scrolled past the nav (solid background)
   .is-hidden   -> user scrolls down (nav slides out), never while the burger menu is open
   Only scrolling by the user (wheel, touch, keys, scrollbar) shows/hides the nav,
   scroll jumps caused by layout changes (e.g. opening the FAQ) are ignored. */
const initStickyNav = (nav) => {
    const tolerance = 8;         // px the user has to scroll before the direction counts
    const inputWindow = 1000;    // ms after a user input in which scrolling counts as "by the user"
    let lastY = window.scrollY;
    let lastInput = -Infinity;
    let ticking = false;

    const markInput = () => { lastInput = performance.now(); };
    ["wheel", "touchmove", "keydown"].forEach((type) =>
        window.addEventListener(type, markInput, { passive: true }));
    // pointerdown directly on the page = scrollbar (clicks on buttons etc. don't count)
    window.addEventListener("pointerdown", (event) => {
        if (event.target === document.documentElement) markInput();
    });

    const update = () => {
        const y = window.scrollY;
        const offset = nav.offsetHeight;

        nav.classList.toggle("is-scrolled", y > offset);

        if (Math.abs(y - lastY) > tolerance) {
            const byUser = performance.now() - lastInput < inputWindow;
            if (byUser) {
                markInput(); // keeps momentum scrolling (touch) counted as user scrolling
                const scrollingDown = y > lastY;
                nav.classList.toggle("is-hidden", scrollingDown && y > offset * 2 && !nav.classList.contains("is-open"));
            }
            lastY = y;
        }

        ticking = false;
    };

    window.addEventListener("scroll", () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(update);
    }, { passive: true });

    update();
};

document.querySelectorAll("nav[data-sticky]").forEach(initStickyNav);
