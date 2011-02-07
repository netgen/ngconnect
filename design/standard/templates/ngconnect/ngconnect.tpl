{def $user = fetch(user, current_user) $method_name = ''}
{if $user.contentobject_id|eq(10)}
	{def $login_methods = ezini('ngconnect', 'LoginMethods', 'ngconnect.ini')}
	{if $login_methods|count}
		{def $login_window_type = ezini('ngconnect', 'LoginWindowType', 'ngconnect.ini')|trim}
		<span id="ngconnect">
			{foreach $login_methods as $l}
				{set $method_name = ezini(concat('LoginMethod_', $l), 'MethodName', 'ngconnect.ini')}
				{if $login_window_type|eq('popup')}
					<a href="#" onclick="window.open('{concat('layout/set/ngconnect/ngconnect/login/', $l)|ezurl(no, full)}', '', 'resizable=1,scrollbars=1,width=800,height=420');return false;"><img src={concat('ngconnect/', $l, '.png')|ezimage} alt="{$method_name|wash}" /></a>
				{else}
					<a href={concat('ngconnect/login/', $l)|ezurl}><img src={concat('ngconnect/', $l, '.png')|ezimage} alt="{$method_name|wash}" /></a>
				{/if}
			{/foreach}
		</span>
	{/if}
{/if}
{undef}