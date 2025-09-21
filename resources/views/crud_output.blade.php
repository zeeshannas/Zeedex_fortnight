<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Grocery CRUD</title>
    @foreach ($css_files as $css_file)
        <link type="text/css" rel="stylesheet" href="{{ $css_file }}" />
    @endforeach
</head>

<body>
    <div style="padding:20px;">
        {!! $output !!}
    </div>

    @foreach ($js_files as $js_file)
        <script src="{{ $js_file }}"></script>
    @endforeach
</body>

</html>
