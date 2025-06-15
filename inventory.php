<?php
session_start();
// Access control
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory | biiApp</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <style>
        /* Optional: Some basic styling for a cleaner look */
        body { font-family: sans-serif; padding: 2em; }
        #inventoryTable input { width: 90%; box-sizing: border-box; }
        .dt-buttons, .dataTables_filter { margin-bottom: 1em; }
        .dt-buttons button, #addNewBtn {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f8f8f8;
            cursor: pointer;
        }
        .dt-buttons button:hover, #addNewBtn:hover { background-color: #e8e8e8; }
        #new-item-row input { border: 1px solid #007bff; }
    </style>
</head>
<body>
    <h2>Inventory Management</h2>

    <div style="margin-bottom: 10px;">
        <button id="addNewBtn">Add New Item</button>
        <button id="deleteSelectedBtn">Delete Selected</button>
    </div>

    <table id="inventoryTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>ID</th>
                <th>Item Name</th>
                <th>DP</th>
                <th>RP</th>
                <th>MRP</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            </tbody>
    </table>

<script>
$(document).ready(function () {
    const table = $('#inventoryTable').DataTable({
        "ajax": "inventory_fetch.php",
        "pageLength": 25,
        "order": [[ 1, "desc" ]], // Order by ID descending by default
        "columns": [
            {
                "data": null,
                "orderable": false,
                "render": function (data, type, row) {
                    return `<input type="checkbox" class="row-check" value="${row.id}">`;
                }
            },
            { "data": "id" },
            { "data": "item_name" },
            { "data": "dp" },
            { "data": "rp" },
            { "data": "mrp" },
            { "data": "quantity" }
        ]
    });

    // --- ADD NEW ITEM ---
    $('#addNewBtn').on('click', function () {
        // Prevent adding a new row if one already exists
        if ($('#new-item-row').length > 0) {
            alert('Please save or cancel the current new item first.');
            return;
        }

        const newRow = `
            <tr id="new-item-row">
                <td></td>
                <td>(new)</td>
                <td><input type="text" name="item_name" placeholder="Item Name"></td>
                <td><input type="number" name="dp" placeholder="DP"></td>
                <td><input type="number" name="rp" placeholder="RP"></td>
                <td><input type="number" name="mrp" placeholder="MRP"></td>
                <td><input type="number" name="quantity" placeholder="Qty"></td>
                <td>
                    <button class="save-new-item">Save</button>
                    <button class="cancel-new-item">Cancel</button>
                </td>
            </tr>`;
        
        // Add the new row to the top of the table body
        $('#inventoryTable tbody').prepend(newRow);
    });

    // Save the new item to the database
    $('#inventoryTable tbody').on('click', '.save-new-item', function () {
        const newRow = $('#new-item-row');
        const itemData = {
            item_name: newRow.find('input[name="item_name"]').val(),
            dp: newRow.find('input[name="dp"]').val(),
            rp: newRow.find('input[name="rp"]').val(),
            mrp: newRow.find('input[name="mrp"]').val(),
            quantity: newRow.find('input[name="quantity"]').val()
        };

        // Basic validation
        if (!itemData.item_name || !itemData.mrp || !itemData.quantity) {
            alert('Item Name, MRP, and Quantity are required.');
            return;
        }

        $.ajax({
            url: 'inventory_add.php',
            type: 'POST',
            data: itemData,
            success: function(response) {
                alert(response);
                table.ajax.reload(); // Reload the table data from the server
            },
            error: function() {
                alert('Error: Could not add the item.');
            }
        });
    });

    // Cancel adding a new item
    $('#inventoryTable tbody').on('click', '.cancel-new-item', function () {
        $('#new-item-row').remove();
    });


    // --- INLINE EDITING (UPDATE) ---
    $('#inventoryTable tbody').on('dblclick', 'td', function () {
        const cell = table.cell(this);
        const column = table.column(this).dataSrc();
        const rowData = table.row(this).data();
        
        // Make sure it's an editable column and not the checkbox or ID column
        const nonEditableColumns = [null, 'id'];
        if (nonEditableColumns.includes(column)) {
            return;
        }

        const oldValue = cell.data();
        const newValue = prompt(`Edit ${column}:`, oldValue);

        if (newValue !== null && newValue !== oldValue) {
            $.post('inventory_update.php', {
                id: rowData.id,
                column: column,
                value: newValue
            }, function (response) {
                alert(response);
                table.ajax.reload(null, false); // Reload without resetting the page
            }).fail(function() {
                alert("Error: Could not update the record.");
            });
        }
    });


    // --- DELETE SELECTED ---
    $('#deleteSelectedBtn').on('click', function () {
        const selectedIds = $('.row-check:checked').map(function () {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('No rows selected to delete.');
            return;
        }

        if (confirm('Are you sure you want to delete the selected ' + selectedIds.length + ' item(s)?')) {
            $.post('inventory_delete.php', { ids: selectedIds }, function (response) {
                alert(response);
                table.ajax.reload();
            }).fail(function() {
                alert("Error: Could not delete the records.");
            });
        }
    });

    // "Select All" checkbox functionality
    $('#selectAll').on('click', function () {
        $('.row-check').prop('checked', this.checked);
    });

    $('#inventoryTable tbody').on('change', '.row-check', function() {
        if (!this.checked) {
            $('#selectAll').prop('checked', false);
        }
    });
});
</script>

</body>
</html>