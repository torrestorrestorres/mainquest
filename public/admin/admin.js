/*

Small helpers for the admin area, activated via data attributes:

    <form data-confirm="Wirklich?">          -> asks before submitting
    <button data-copy>                       -> copies the value of the input next to it
    <select data-autosubmit>                 -> submits its form on change
    <input type="file" data-preview-target="x"> + <img data-preview="x">  -> shows the chosen image

*/

document.addEventListener("submit", (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
});

document.addEventListener("click", async (event) => {
    const button = event.target.closest("[data-copy]");
    if (!button) return;

    const input = button.parentElement.querySelector("input");
    input.select();
    try {
        await navigator.clipboard.writeText(input.value);
        button.textContent = "Kopiert ✓";
    } catch {
        button.textContent = "Mit Strg+C kopieren";
    }
});

document.addEventListener("change", (event) => {
    const target = event.target;

    if (target.matches("[data-autosubmit]")) {
        target.form.requestSubmit();
    }

    if (target.matches("[data-preview-target]") && target.files[0]) {
        const preview = document.querySelector(`[data-preview="${target.dataset.previewTarget}"]`);
        if (preview) preview.src = URL.createObjectURL(target.files[0]);
    }
});
