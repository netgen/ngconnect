<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="ngconnect-profile">
	<div class="attribute-header">
		<h1 class="long">{'Profile setup'|i18n('extension/ngconnect/ngconnect/profile')}</h1>
	</div>

	{if is_set($bad_login)}
		<div class="warning">
			<p>{'A valid username and password is required to login.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
		</div>
	{elseif is_set($login_not_allowed)}
		<div class="warning">
			<p>{'You are not allowed to access the site.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
		</div>
	{elseif is_set($validation_error)}
		<div class="warning">
			<p>{$validation_error|wash}</p>
		</div>
	{/if}

	<h2>{'Welcome'|i18n('extension/ngconnect/ngconnect/profile')}</h2>

	<p>{'One more step is needed to activate your account and to be able to sign in using regular username and password.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
	<p>{'If you already have a regular account, input your details under "Login to existing account" section.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
	<p>{'If you do not have a regular account, enter your desired username and password under "Create new account" section.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
	<p>{'If you do not wish to create a regular account, simply click the "Skip" button below, and we won\'t bother you with this again.'|i18n('extension/ngconnect/ngconnect/profile')}</p>

	<div class="block">
		<form action={'ngconnect/profile'|ezurl} method="post">
			<div class="buttonblock">
				<input class="defaultbutton" type="submit" name="SkipButton" value="{'Skip'|i18n('extension/ngconnect/ngconnect/profile')}" tabindex="1" />
			</div>
		</form>
	</div>

	<h2>{'Login to existing account'|i18n('extension/ngconnect/ngconnect/profile')}</h2>

	<div class="block">
		<form action={'ngconnect/profile'|ezurl} method="post">
			<label>{'Username'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="text" size="10" name="Login" id="id1" value="{cond(ezhttp_hasvariable('Login', 'post'), ezhttp('Login', 'post'), '')}" tabindex="2" />

			<label>{'Password'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="password" size="10" name="Password" id="id2" value="" tabindex="3" />

			<div class="buttonblock">
				<input class="defaultbutton" type="submit" name="LoginButton" value="{'Login'|i18n('extension/ngconnect/ngconnect/profile')}" tabindex="4" />
			</div>
		</form>
	</div>

	<h2>{'Create new account'|i18n('extension/ngconnect/ngconnect/profile')}</h2>

	<div class="block">
		<form action={'ngconnect/profile'|ezurl} method="post">
			<label>{'Username'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="text" size="10" name="data_user_login" value="{cond(ezhttp_hasvariable('data_user_login', 'post'), ezhttp('data_user_login', 'post'), '')}" tabindex="5" />

			<label>{'E-mail'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="text" size="10" name="data_user_email" value="{cond(and(ezhttp_hasvariable('data_user_email', 'post'), ezhttp('data_user_email', 'post')|count), ezhttp('data_user_email', 'post'), $network_email)}" tabindex="6" />

			<label>{'Password'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="password" size="10" name="data_user_password" value="" tabindex="7" />

			<label>{'Repeat password'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="password" size="10" name="data_user_password_confirm" value="" tabindex="8" />

			<div class="buttonblock">
				<input class="defaultbutton" type="submit" name="SaveButton" value="{'Save'|i18n('extension/ngconnect/ngconnect/profile')}" tabindex="9" />
			</div>
		</form>
	</div>
</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>