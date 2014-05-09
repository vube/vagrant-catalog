<html>
<head>
    <title>Vagrant Catalog</title>
</head>
<body>
<h1>Vagrant Catalog</h1>
<h2>{$pathInfo|escape}</h2>

{if $boxes|@count gt 0}
<h3>Boxes</h3>
{foreach $boxes as $file}
    <li><a href="{$CATALOG_URI|escape}{$pathInfo|escape}">{$pathInfo|escape}</a></li>
{/foreach}
{/if}

{if $directories|@count gt 0}
<h3>Sub-directories</h3>
<ul>
{foreach $directories as $dir}
    <li><a href="{$BASE_URI|escape}{$pathInfo|escape}/{$dir|escape}">{$dir|escape}</a></li>
{/foreach}
</ul>
{/if}

</body>
</html>
