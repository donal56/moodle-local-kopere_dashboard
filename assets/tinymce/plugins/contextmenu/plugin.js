tinymce.PluginManager.add("contextmenu", function (e) {
    var n, t = e.settings.contextmenu_never_use_native;
    e.on("contextmenu", function (i) {
        var o;
        if (!i.ctrlKey || t) {
            if (i.preventDefault(), o = e.settings.contextmenu || "link image inserttable | cell row column deletetable", n)n.show(); else {
                var c = [];
                tinymce.each(o.split(/[ ,]/), function (n) {
                    var t = e.menuItems[n];
                    "|" == n && (t = {text: n}), t && (t.shortcut = "", c.push(t))
                });
                for (var a = 0; a < c.length; a++)"|" == c[a].text && (0 === a || a == c.length - 1) && c.splice(a, 1);
                n = new tinymce.ui.Menu({items: c, context: "contextmenu"}).addClass("contextmenu").renderTo(), e.on("remove", function () {
                    n.remove(), n = null
                })
            }
            var l = {x: i.pageX, y: i.pageY};
            e.inline || (l = tinymce.DOM.getPos(e.getContentAreaContainer()), l.x += i.clientX, l.y += i.clientY), n.moveTo(l.x, l.y)
        }
    })
});