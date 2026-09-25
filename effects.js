/*

Page-wide effects that are not components.
Activated via data attributes in index.html:

    <nav data-sticky>   -> nav sticks to the top, hides on scroll down, shows on scroll up

*/

/* Sticky navigation
   .is-scrolled -> page is scrolled past the nav (solid background)
   .is-hidden   -> user scrolls down (nav slides out), never while the burger menu is open */
const initStickyNav = (nav) => {
    const tolerance = 8; // px the user has to scroll before the direction counts
    let lastY = window.scrollY;
    let ticking = false;

    const update = () => {
        const y = window.scrollY;
        const offset = nav.offsetHeight;

        nav.classList.toggle("is-scrolled", y > offset);

        if (Math.abs(y - lastY) > tolerance) {
            const scrollingDown = y > lastY;
            nav.classList.toggle("is-hidden", scrollingDown && y > offset * 2 && !nav.classList.contains("is-open"));
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
