{extends file="layouts/page.tpl"}

{block name=body}
<h1>{$data.title}</h1>

<div id="body">{$data.body}</div>

{include file="partials/footer.tpl"}

{/block}
