<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>DevExpress DataGrid - Products</title>

    <!-- DevExpress CSS -->
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/23.1.3/css/dx.light.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DevExpress JS -->
    <script src="https://cdn3.devexpress.com/jslib/23.1.3/js/dx.all.js"></script>
</head>

<body>
    <h2 style="text-align:center;">Products - DevExpress DataGrid</h2>
    <div id="gridContainer" style="height:600px; margin:20px;"></div>

    <script>
        $(function() {
            var store = new DevExpress.data.CustomStore({
                key: "id",
                load: function() {
                    return $.getJSON("/api/products");
                },
                insert: function(values) {
                    return $.ajax({
                        url: "/api/products",
                        method: "POST",
                        data: values
                    });
                },
                update: function(key, values) {
                    return $.ajax({
                        url: "/api/products/" + key,
                        method: "PUT",
                        data: values
                    });
                },
                remove: function(key) {
                    return $.ajax({
                        url: "/api/products/" + key,
                        method: "DELETE"
                    });
                }
            });

            $("#gridContainer").dxDataGrid({
                dataSource: store,
                keyExpr: "id",
                editing: {
                    mode: "row",
                    allowAdding: true,
                    allowUpdating: true,
                    allowDeleting: true
                },
                columns: [{
                        dataField: "id",
                        caption: "ID",
                        allowEditing: false
                    },
                    {
                        dataField: "title",
                        caption: "Title"
                    },
                    {
                        dataField: "category_id",
                        caption: "Category ID"
                    },
                    {
                        dataField: "subcategory_id",
                        caption: "Subcategory ID"
                    },
                    {
                        dataField: "expiry_date",
                        caption: "Expiry Date",
                        dataType: "date"
                    },
                    {
                        dataField: "status",
                        caption: "Status",
                        dataType: "boolean"
                    }
                ]
            });
        });
    </script>

</body>

</html>
