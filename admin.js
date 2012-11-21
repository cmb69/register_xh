/**
 * Back-End JS of Register_XH.
 *
 * Copyright (c) 2012 Christoph M. Becker (see license.txt)
 */


// TODO: general iteraterators (forEachUserRow() etc.) ?

var register = {
    
    init: function() {
        var rows = document.getElementsByTagName('tr');
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            if (row.className == "register_second_row") {
                row.style.display = "none";
            }
        }
        
        var gsel = document.getElementById("register_group_selectbox");
        gsel.onchange = function() {
            register.filterByGroup(this.value);
        }

        var f = document.getElementById('register_user_form');
        var elts = f.elements;
        for (var i = 0, j = 0; i < elts.length; i++) {
            var elt = elts[i];
            elt.onchange = function() {
                window.onbeforeunload = function() {
                    return register.tx.confirmLeave;
                }
            }
        }
        f.onsubmit = function() {
            window.onbeforeunload = null;
        }

    },
    
    sort: function(heading, col) {
        
        var ths = document.getElementsByTagName("th");
        for (var i = 0; i < ths.length; i++) {
            var th = ths[i];
            if (th != heading && th.parentNode.parentNode.parentNode.id == "register_user_table") {
                th.className = "";
            }
        }
        
        var names = [];
        var f = document.getElementById('register_user_form');
        var elts = f.elements;
        for (var i = 0, j = 0; i < elts.length; i++) {
            var elt = elts[i];
            if (elt.name.indexOf(col) === 0) {
                var name = (elt.nodeName == 'SELECT')
                    ? elt.options[elt.selectedIndex].text
                    : elt.value;
                names.push([name, j++]);
            }
        }
        names.sort(function(a, b) {return a[0].toLowerCase().localeCompare(b[0].toLowerCase())});
        if (heading.className == "register_sort_asc") {
            names.reverse();
        }
        var tblBody = document.getElementById('register_user_table').firstChild;
        for (var i = 0; i < names.length; i++) {
            var name = names[i];
            var j = name[1];
            var row = document.getElementById('register_user_' + j);
            var row2 = row.nextSibling;
            tblBody.appendChild(row);
            tblBody.appendChild(row2);
        }
        heading.className = heading.className == "register_sort_asc"
            ? "register_sort_desc"
            : "register_sort_asc";
        register.renumberRows();
    },
    
    toggleDetails: function() {
        var display = document.getElementById("register_toggle_details").checked ? "" : "none";
        var rows = document.getElementsByTagName('tr');
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            if (row.className == "register_second_row" && row.previousSibling.style.display != "none") {
                row.style.display = display;
            }
        }
    },
    
    removeRow: function(btn) {
        var row = btn.parentNode.parentNode;
        row.parentNode.removeChild(row.nextSibling);
        row.parentNode.removeChild(row);
        register.renumberRows();
    },
    
    
    addRow: function() {
        document.getElementById("register_group_selectbox").value = "";
        
        var tbl = document.getElementById('register_user_table');
        var tpl = document.getElementById('register_user_template');
        var tpl2 = tpl.nextSibling.cloneNode(true);
        tpl = tpl.cloneNode(true);
        tbl.firstChild.appendChild(tpl);
        tbl.firstChild.appendChild(tpl2);
        tpl.style.display = tpl2.style.display = "table-row";
        tpl2.className = "register_second_row";
        register.renumberRows();
        register.filterByGroup(document.getElementById("register_group_selectbox").value);
        document.getElementById("register_toggle_details").checked = true;
        register.toggleDetails();
        window.onbeforeunload = function() {
            return register.tx.confirmLeave;
        }
        tpl.firstChild.nextSibling.firstChild.focus();
    },
    
    renumberRows: function() {
        var rows = document.getElementsByTagName('tr');
        for (var i = 0, j = 0; i < rows.length; i++) {
            var row = rows[i];
            if (row.parentNode.parentNode.id == "register_user_table"
                && row.id.indexOf("register_user_") === 0)
            {
                row.id = "register_user_" + j++;
            }
        }
    },
    
    filterByGroup: function(group) {
        var f = document.getElementById('register_user_form');
        var elts = f.elements;
        for (var i = 0, j = 0; i < elts.length; i++) {
            var elt = elts[i];
            if (elt.name.indexOf("accessgroups") === 0) {
                var row = elt.parentNode.parentNode;
                var row2 = row.nextSibling;
                row.style.display = elt.value.indexOf(group) >= 0 ? "" : "none";
                row2.style.display = elt.value.indexOf(group) >= 0
                    && document.getElementById("register_toggle_details").checked
                    ? "" : "none"
            }
        }
    }
}