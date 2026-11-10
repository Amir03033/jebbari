/* Runs before first paint. Enables hidden start states for reveals. If the
   main bundle has not confirmed within 3s, content is shown rather than left hidden. */
(function (d) {
    var h = d.documentElement;
    h.classList.add('js');
    setTimeout(function () {
        if (!h.classList.contains('motion-ready')) {
            h.classList.remove('js');
        }
    }, 3000);
})(document);

/* Theme: light unless the visitor chose dark with the switch. Runs before first paint
   so there is no flash. Storage can be unavailable (private windows): ignore. */
(function (d) {
    try {
        var t = localStorage.getItem('theme');
        if (t === 'dark' || t === 'light') {
            d.documentElement.setAttribute('data-theme', t);
        }
    } catch (e) {}
})(document);
