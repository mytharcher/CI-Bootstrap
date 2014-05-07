<!DOCTYPE html>
<html lang="zh-CN" class="fullpage">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow" />
<title>管理</title>

<link rel="stylesheet" type="text/css" href="/assets/css/admin.pack.css">
<style type="text/css">
.layout-container{
	background-color: #fff;
	box-shadow: 0 0 0 rgba(0,0,0,0);
	-webkit-transition: box-shadow .5s ease, background-color .5s ease;
	-moz-transition: box-shadow .5s ease, background-color .5s ease;
	-ms-transition: box-shadow .5s ease, background-color .5s ease;
	transition: box-shadow .5s ease, background-color .5s ease;
}
.layout-container:hover{
	background-color: #fff;
	box-shadow: 0 0 5px rgba(0,0,0,.15);
}

.layout-sidebar{
	width: 15%;
	height: 100%;
	overflow: auto;
}
.layout-sidebar .layout-container{
	margin: 1em .5em 1em 1em;
}
.layout-meta{
	background: #333;
	color: #ccc;
}
.layout-meta:hover{
	background-color: #000;
}
.layout-meta .btn-link{
	color: #ccc;
	text-shadow: none;
}
.layout-meta .btn[type=button]{
	font-weight: bold;
}
.layout-meta .btn-link:hover{
	color: #fff;
}
.layout-meta .btn[type=submit]{
	float: right;
}
.layout-meta .btn[type=submit]:hover{
	background: #c00;
}

.layout-mainblock{
	position: fixed;
	left: 15%;
	width: 85%;
	height: 100%;
	overflow: auto;
	padding: 1em 1em 1em .5em;
}
.layout-mainblock .layout-container{
	min-height: 100%;
	padding: 2em;
}
</style>
</head>
<body>

<div class="layout-fixed layout-sidebar">

	<div class="layout-container layout-nav">
		<ul id="Nav" class="nav nav-list">
			<li class="nav-header">系统管理</li>
			<li><a href="#/dashboard">仪表盘</a></li>
			{if $permissions['*'] || $permissions['account*']}
			<li><a href="#/account">账号</a></li>
			{/if}
			{if $permissions['*'] || $permissions['role*']}
			<li><a href="#/authorization">角色权限</a></li>
			{/if}
			<li class="nav-header">数据管理</li>
			{if $permissions['*'] || $permissions['post*']}
			<li><a href="#/post">内容</a></li>
			{/if}
		</ul>
	</div>

	<div class="layout-container layout-meta">
		<form method="post" action="/account/logout" ui="type:XAjaxForm;id:LoginForm">
			<a class="btn btn-link">{$session.name}</a>
			<button type="submit" ui="type:Button;" class="btn btn-link">退出</button>
		</form>
	</div>
</div>

<div class="layout-mainblock">
	<div class="layout-container" id="Main"></div>
</div>

<script src="http://elfjs.qiniudn.com/code/elf-0.5.0.min.js"></script>
<script src="http://elfjs.qiniudn.com/code/er.min.js"></script>
<script src="http://elfjs.qiniudn.com/code/esui.min.js"></script>
<script src="/assets/js/site.admin.pack.js"></script>
<script>
site.lib.Main.setup();
</script>

</body>
</html>