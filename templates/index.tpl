<html>
<head>
    <title>Vagrant Catalog</title>
</head>
<body>
<h1>Vagrant Catalog</h1>
<h2>{$relativePathInfo|escape}</h2>

{if $boxes|@count gt 0}
<h3>Boxes</h3>
<ul>
{foreach $boxes as $file}
    <li><a href="{$BASE_URI|escape}{$pathInfo|escape}/{$file|escape}">{$relativePathInfo|escape}{if $relativePathInfo ne ''}/{/if}{$file|escape}</a></li>
{/foreach}
{/if}
</ul>

{if $directories|@count gt 0}
<h3>Sub-directories</h3>
<ul>
{foreach $directories as $dir}
    <li><a href="{$BASE_URI|escape}{$pathInfo|escape}/{$dir|escape}">{$dir|escape}</a></li>
{/foreach}
</ul>
{/if}

{if $metadata !== null}
<h3><a href="{$CATALOG_URI|escape}{$pathInfo|escape}">{$CATALOG_URI|escape}{$pathInfo|escape}</a></h3>
<pre>{$metadata|escape}</pre>
{/if}

<p><em>Powered by <a href="https://github.com/vube/vagrant-catalog">vagrant-catalog</a></em></p>

</body>
</html>
