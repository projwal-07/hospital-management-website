/* =========================================================
   Evergreen Community Hospital - Shared JavaScript
   Controls the mobile menu, contact form and gallery lightbox.
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    setupMobileMenu();
    setupContactForm();
    setupGallery();
    updateCopyrightYear();
});

function setupMobileMenu() {
    const menuButton = document.querySelector(".menu-button");
    const navigation = document.querySelector(".main-nav");

    if (!menuButton || !navigation) return;

    /* Keep the visible menu state and aria-expanded value in sync so the
       mobile navigation remains understandable for screen-reader users. */
    menuButton.addEventListener("click", function () {
        const isOpen = navigation.classList.toggle("open");
        menuButton.classList.toggle("open", isOpen);
        menuButton.setAttribute("aria-expanded", String(isOpen));
        menuButton.setAttribute(
            "aria-label",
            isOpen ? "Close navigation menu" : "Open navigation menu"
        );
    });
}

function setupContactForm() {
    const form = document.querySelector("#enquiry-form");
    const feedback = document.querySelector("#form-feedback");

    if (!form || !feedback) return;

    /* This prototype validates the request locally and displays feedback,
       but it does not send or store any patient information. */
    form.addEventListener("submit", function (event) {
        event.preventDefault();
        feedback.className = "form-feedback";

        if (!form.checkValidity()) {
            feedback.textContent =
                "Please complete all required fields using the correct format.";
            feedback.classList.add("error");
            form.reportValidity();
            feedback.focus();
            return;
        }

        feedback.textContent =
            "Thank you. Your message has been checked for this website demonstration. No information has been sent or stored.";
        feedback.classList.add("success");
        form.reset();
        feedback.focus();
    });
}

function setupGallery() {
    const lightbox = document.querySelector("#lightbox");
    const galleryButtons = document.querySelectorAll(".gallery-button");

    if (!lightbox || galleryButtons.length === 0) return;

    const largeImage = lightbox.querySelector("img");
    const caption = lightbox.querySelector("figcaption");
    const closeButton = lightbox.querySelector(".lightbox-close");
    let previousFocus = null;

    function openLightbox(button) {
        const thumbnail = button.querySelector("img");

        /* Save the thumbnail that opened the lightbox so keyboard users return
           to the same place after closing the enlarged gallery image. */
        previousFocus = button;
        largeImage.src = thumbnail.src;
        largeImage.alt = thumbnail.alt;
        caption.textContent = button.dataset.caption;
        lightbox.classList.add("visible");
        lightbox.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
        closeButton.focus();
    }

    function closeLightbox() {
        lightbox.classList.remove("visible");
        lightbox.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";

        if (previousFocus) previousFocus.focus();
    }

    galleryButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            openLightbox(button);
        });
    });

    closeButton.addEventListener("click", closeLightbox);

    lightbox.addEventListener("click", function (event) {
        if (event.target === lightbox) closeLightbox();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && lightbox.classList.contains("visible")) {
            closeLightbox();
        }
    });
}

function updateCopyrightYear() {
    const year = document.querySelector("#current-year");
    if (year) year.textContent = new Date().getFullYear();
}
