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
