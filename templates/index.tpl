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

<h2>Version {$json['versions'][0]['version']|escape}</h2>
<p>
    {foreach $json['versions'][0]['providers'] as $provider}
        <a href="{$provider['url']|escape}">{$provider['name']|escape}</a>
        {$provider['checksum_type']|escape}={$provider['checksum']|escape}
        <br/>
    {/foreach}
</p>

{if $json['versions']|@count gt 1}
<h3>Older Versions</h3>
{foreach $json['versions'] as $version}
{if ! $version@first}{* do not print the first version, we've printed it above *}
    <h4>Version {$version['version']|escape}</h4>
    <p>
        {foreach $version['providers'] as $provider}
            <a href="{$provider['url']|escape}">{$provider['name']|escape}</a>
            {$provider['checksum_type']|escape}={$provider['checksum']|escape}
            <br/>
        {/foreach}
    </p>
{/if}{* end if not the first version *}
{/foreach}
{/if}{* end if more than 1 json version *}

{/if}

<p><em>Powered by <a href="https://github.com/vube/vagrant-catalog">vagrant-catalog</a></em></p>

</body>
</html>
