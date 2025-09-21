<!DOCTYPE html>
<html>

<head>
    <title>DevExpress Grid</title>
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/22.2.5/css/dx.light.css">
    <script src="https://cdn3.devexpress.com/jslib/22.2.5/js/jquery.min.js"></script>
    <script src="https://cdn3.devexpress.com/jslib/22.2.5/js/dx.all.js"></script>
</head>

<body>
    <div id="gridContainer"></div>

    <script>
        $(function() {
            $("#gridContainer").dxDataGrid({
                dataSource: '/api/categories',
                editing: {
                    mode: "row",
                    allowUpdating: true,
                    allowAdding: true,
                    allowDeleting: true
                },
                columns: ["id", "title", "status"]
            });
        });
    </script>
</body>

</html>
