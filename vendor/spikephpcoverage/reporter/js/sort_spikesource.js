/*---------------------------------------------------\
| Table Sorter                                       |
|----------------------------------------------------|
| Author: Vinay Srinivasaiah (vsrini@spikesource.com)|
| SpikeSource (http://www.spikesource.com)           |
| - DOM 1 based script that makes the table sortable.|
| - Copyright (c) 2004 SpikeSource Inc.              |
|---------------------------------------------------*/
//http://www.w3.org/TR/REC-DOM-Level-1/java-language-binding.html

var tableBody;
var table2sort;
var imgUp;
var imgDown;

function TableSorter(table) {
    this.table2sort = table;
    this.tableBody = this.table2sort.getElementsByTagName("tbody")[0];

    this.imgUp = document.createElement("img");
    this.imgUp.src = "images/arrow_up.gif";
    this.imgDown = document.createElement("img");
    this.imgDown.src = "images/arrow_down.gif";
}

var lastSortCol = -1;
var lastSortOrderAsc = true;
var origChildRows;

function createImgLink(row, imageSrc) {
    var cell = row.cells[0];
    var id = _getInnerText(cell) + "_" + imageSrc;

    imgExpand = document.createElement("img");
    imgExpand.src = "images" + imageSrc + ".gif";
    imgExpand.border="0";

    imgBlank = document.createElement("img");
    imgBlank.src = "results/images/transdot.gif";
    imgBlank.border="0";
    imgBlank2 = imgBlank.cloneNode(false);
    imgBlank3 = imgBlank.cloneNode(false);

    anchorTag = document.createElement("a");
    anchorTag.href="javascript:toggleShowChildren('" + id + "');"
    anchorTag.appendChild(imgExpand);
    anchorTag.appendChild(imgBlank);
    anchorTag.appendChild(imgBlank2);
    anchorTag.appendChild(imgBlank3);
    anchorTag.id = id;

    cell.id = id + "_cell";
    row.id = id + "_row";

    cell.insertBefore(anchorTag, cell.firstChild);
}

TableSorter.prototype.initTable = function () {
    this.populateChildRowsMap();
    for (i = 0; i < origChildRows.length; i++) {
        if (origChildRows[i].id != "indented_row") {
            createImgLink(origChildRows[i], "minus");
        }
    }
}

TableSorter.prototype.collapseAllChildren = function () {
    for (i = 0; i < origChildRows.length; i++) {
        if (origChildRows[i].id != "indented_row") {
            id = _getInnerText(origChildRows[i].cells[0]) + "_" + "minus";
            var anchorTag = document.getElementById(id);
            if (anchorTag != null) {
                this.togglechildren(id);
            }
        }
    }
}

TableSorter.prototype.expandAllChildren = function () {
    for (i = 0; i < origChildRows.length; i++) {
        if (origChildRows[i].id != "indented_row") {
            id = _getInnerText(origChildRows[i].cells[0]) + "_" + "plus";
            var anchorTag = document.getElementById(id);
            if (anchorTag != null) {
                this.togglechildren(id);
            }
        }
    }
}

TableSorter.prototype.togglechildren = function (id) {
    anchorTag = document.getElementById(id);
    anchorParent = document.getElementById((id + "_cell"));
    anchorParent.removeChild(anchorTag);
    row = document.getElementById((id + "_row"));
    nextRow = row.nextSibling;

    var addChildren = false;
    if (anchorTag.firstChild.src.indexOf("plus") != -1) {
        addChildren = true;
        createImgLink(row, "minus");
    } else if (anchorTag.firstChild.src.indexOf("minus") != -1) {
        addChildren = false;
        createImgLink(row, "plus");
    }
    for (i = 0; i < origChildRows.length; i++) {
        //alert("comparing " + _getInnerText(origChildRows[i].cells[0]) 
        // + " and " + _getInnerText(row.cells[0]));
        if (_getInnerText(origChildRows[i].cells[0]) == _getInnerText(row.cells[0])) {
            for (j = i + 1; j < origChildRows.length; j++) {
                if (origChildRows[j].id == "indented_row") {
                    if (addChildren) {
                        this.tableBody.insertBefore(origChildRows[j], nextRow);
                    } else {
                        this.tableBody.removeChild(origChildRows[j]);
                    }
                } else {
                    // done;
                    break;
                }
            }
            break;
        }
    }
}

TableSorter.prototype.populateChildRowsMap = function () {
    var rows = this.tableBody.rows;
    origChildRows = new Array();
    var count = 0;
    var newRowsCount = 0;
    for (i = 0; i < rows.length; i ++) {
        if (rows[i].id == "indented_row") {
            if (parentRow != null) {
                origChildRows[count++] = parentRow;
                parentRow = null;
            }
            origChildRows[count++] = rows[i];
        } else {
            parentRow = rows[i];
        }
    }
}

TableSorter.prototype.sort = function (col, type) {
    if (lastSortCol != -1) {
        sortCell = document.getElementById("sortCell" + lastSortCol);
        if (sortCell != null) {
            if (lastSortOrderAsc == true) {
                sortCell.removeChild(this.imgUp);
            } else {
                sortCell.removeChild(this.imgDown);
            }
        }
        sortLink = document.getElementById("sortCellLink" + lastSortCol);
        if(sortLink != null) {
            sortLink.title = "Sort Ascending";
        }
    }

    if (lastSortCol == col) {
        lastSortOrderAsc = !lastSortOrderAsc;
    } else {
        lastSortCol = col;
        lastSortOrderAsc = true;
    }

    var rows = this.tableBody.rows;
    var newRows = new Array();
    var parentRow;

    var childRows = new Array();
    var count = 0;
    var newRowsCount = 0;
    for (i = 0; i < rows.length; i ++) {
        if (rows[i].id == "indented_row") {
            if (parentRow != null) {
                childRows[count++] = parentRow;
                parentRow = null;
            }
            childRows[count++] = rows[i];
        } else {
            newRows[newRowsCount++] = rows[i];
            parentRow = rows[i];
        }
    }

    // default
    sortFunction = sort_caseInsensitive;
    if (type == "string") sortFunction = sort_caseSensitive;
    if (type == "percentage") sortFunction = sort_numericPercentage;
    if (type == "number") sortFunction = sort_numeric;
    
    newRows.sort(sortFunction);

    if (lastSortOrderAsc == false) {
        newRows.reverse();
    }

    for (i = 0; i < newRows.length; i ++) {
        this.table2sort.tBodies[0].appendChild(newRows[i]);
        var parentRowText = _getInnerText(newRows[i].cells[0]);
        var match = -1;
        for (j = 0; j < childRows.length; j++) {
            var childRowText = _getInnerText(childRows[j].cells[0]);
            if (childRowText == parentRowText) {
                match = j;
                break;
            }
        }
        if (match != -1) {
            for (j = match + 1; j < childRows.length; j++) {
                if (childRows[j].id == "indented_row") {
                    this.table2sort.tBodies[0].appendChild(childRows[j]);
                } else {
                    break;
                }
            }
        }
    }

    sortCell = document.getElementById("sortCell" + col);
    if (sortCell == null) {
    } else {
        if (lastSortOrderAsc == true) {
            sortCell.appendChild(this.imgUp);
        } else {
            sortCell.appendChild(this.imgDown);
        }
    }

    sortLink = document.getElementById("sortCellLink" + col);
    if (sortLink == null) {
    } else {
        if (lastSortOrderAsc == true) {
            sortLink.title = "Sort Descending";
        } else {
            sortLink.title = "Sort Ascending";
        }
    }
}

function sort_caseSensitive(a, b) {
    aa = _getInnerText(a.cells[lastSortCol]);
    bb = _getInnerText(b.cells[lastSortCol]);
    return compareString(aa, bb);
}

function sort_caseInsensitive(a,b) {
    aa = _getInnerText(a.cells[lastSortCol]).toLowerCase();
    bb = _getInnerText(b.cells[lastSortCol]).toLowerCase();
    return compareString(aa, bb);
}

function sort_numeric(a,b) {
    aa = _getInnerText(a.cells[lastSortCol]);
    bb = _getInnerText(b.cells[lastSortCol]);
    return compareNumber(aa, bb);
}

function sort_numericPercentage(a,b) {
    aa = _getInnerText(a.cells[lastSortCol]);
    bb = _getInnerText(b.cells[lastSortCol]);

    var aaindex = aa.indexOf("%");
    var bbindex = bb.indexOf("%");

    if (aaindex != -1 && bbindex != -1) {
        aa = aa.substring(0, aaindex);
        bb = bb.substring(0, bbindex);
        return compareNumber(aa, bb);
    }

    return compareString(aa, bb);
}

function compareString(a, b) {
    if (a == b) return 0;
    if (a < b) return -1;
    return 1;
}

function compareNumber(a, b) {
    aa = parseFloat(a);
    if (isNaN(aa)) aa = 0;
    bb = parseFloat(b);
    if (isNaN(bb)) bb = 0;
    return aa-bb;
}

function _getInnerText(el) {
    if (typeof el == "string") return el;
    if (typeof el == "undefined") { return el };
    if (el.innerText) return el.innerText;
    var str = "";
    
    var cs = el.childNodes;
    var l = cs.length;
    for (var i = 0; i < l; i++) {
        switch (cs[i].nodeType) {
            case 1: //ELEMENT_NODE
                str += _getInnerText(cs[i]);
                break;
            case 3: //TEXT_NODE
                str += cs[i].nodeValue;
                break;
        }
    }
    return str;
}
