function initSectionSlider(sectionClass) {
    const selector = `.${sectionClass}_slider`;

    if (!$(selector).length) return;

    $(selector).slick({
        dots: true,
        arrows: false,
        autoplay: true,
        autoplaySpeed: 3000
    });
}