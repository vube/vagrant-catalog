<html>
<head>
    <title>Vagrant Catalog</title>
    <style type="text/css">
        body { color: #333 }
        a { color: #2a97ce }
        a:visited { color: #2a97ce }
        pre { background-color: #eee }
    </style>
</head>
<body>
<h1>
    <a href="{$BASE_URI|escape}/">Vagrant Catalog</a>
    {if $relativeBackDirs|@count gt 0}:{/if}
{foreach from=$relativeBackDirs key=path item=subdir name=breadcrumb}
    {if $smarty.foreach.breadcrumb.last}
        {$subdir|escape}
    {else}
        <a href="{$BASE_URI|escape}/{$path|escape}">{$subdir|escape}</a> /
    {/if}
{/foreach}
</h1>

{if $boxes|@count gt 0}
<h2>Boxes</h2>
<ul>
{foreach $boxes as $file}
    <li><a href="{$BASE_URI|escape}{$pathInfo|escape}/{$file|escape}">{$relativePathInfo|escape}{if $relativePathInfo ne ''}/{/if}{$file|escape}</a></li>
{/foreach}
{/if}
</ul>

{if $directories|@count gt 0}
<h2>Sub-directories</h2>
<ul>
{foreach $directories as $dir}
    <li><a href="{$BASE_URI|escape}{$pathInfo|escape}/{$dir|escape}">{$dir|escape}</a></li>
{/foreach}
</ul>
{/if}

{if $metadata !== null}
<h2>Vagrant config</h2>
<p><pre>
Vagrant.configure(2) do |config|

  config.vm.box = "{$relativePathInfo|escape}"
  config.vm.box_url = '<a href="{$CATALOG_URI|escape}{$pathInfo|escape}">{$CATALOG_URI|escape}{$pathInfo|escape}</a>'

  # Whatever other config stuff you want to do
end
</pre></p>

<h3>Metadata</h3>
<pre>{$metadata|escape}</pre>
{/if}

<p><em>Powered by <a href="https://github.com/vube/vagrant-catalog">vagrant-catalog</a></em></p>

</body>
</html>
