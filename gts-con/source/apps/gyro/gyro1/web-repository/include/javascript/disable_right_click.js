if (document.images) {
    for (i = 0; i < document.images.length; i++) {
        document.images[i].oncontextmenu = function() { return false };
    }
}
