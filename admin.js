var register = {
    sort: function() {
        var names = [];
        var f = document.getElementById('register_user_form');
        var elts = f.elements;
        for (var i = 0; i < elts.length; i++) {
            elt = elts[i];
            if (elt.name.indexOf('username') === 0) {
                names.push(elt.value);
            }
        }
        var old = names.slice();
        names.sort();
        var tbl = document.getElementById('register_user_table');
        for (var i = 0; i < names.length; i++) {
            var name = names[i];
            var j = old.indexOf(names[i]);
            var row = document.getElementById('register_user_' + j);
            var row2 = row.nextSibling;
            tbl.appendChild(row);
            tbl.appendChild(row2);
        }
        
    }
}