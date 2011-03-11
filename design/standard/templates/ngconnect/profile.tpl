{def $attribute = $ngconnect_user.contentobject.data_map.user_account}
{def $id_base = concat('ezcoa-', $attribute.contentclassattribute_id, '_', $attribute.contentclass_attribute_identifier)}
{def $attribute_base = 'ContentObjectAttribute'}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="ngconnect-profile">
	<div class="attribute-header">
		<h1 class="long">{'Profile setup'|i18n('extension/ngconnect/ngconnect/profile')}</h1>
	</div>

	{if $attribute.has_validation_error}
		<div class="warning">
			<p>{$attribute.validation_error|wash}</p>

			{if ezhttp_hasvariable(concat($attribute_base, '_data_user_login_', $attribute.id), 'post')}
				{if user_exists(ezhttp(concat($attribute_base, '_data_user_login_', $attribute.id), 'post'))}
					<p>{'If you are trying to login to an existing regular account, sign in with that account first and then select the option of connecting with social network of your choice.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
				{/if}
			{/if}
		</div>
	{/if}

	<p>{'Welcome and thank you for signing up to our site with your social network account.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
	<p>{'If you would like to be able to login to the site by also using a regular account, enter your details below and click the "Save" button.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
	<p>{'If you don\'t want to create a regular account and just keep using social networks for login purposes, simply click the "Skip" button below.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
	<p>{'You can also select "Don\'t ask me again" option and we won\'t bother you with this question ever again.'|i18n('extension/ngconnect/ngconnect/profile')}</p>
	<p>&nbsp;</p>

	<form action={'ngconnect/profile'|ezurl} method="post">
		<div class="block">
			<label>{'Username'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="text" size="10" id="{$id_base}_login" name="{$attribute_base}_data_user_login_{$attribute.id}" value="{cond(ezhttp_hasvariable(concat($attribute_base, '_data_user_login_', $attribute.id), 'post'), ezhttp(concat($attribute_base, '_data_user_login_', $attribute.id), 'post'), '')}" tabindex="1" />

			<label>{'E-mail'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="text" size="10" id="{$id_base}_email" name="{$attribute_base}_data_user_email_{$attribute.id}" value="{cond(ezhttp_hasvariable(concat($attribute_base, '_data_user_email_', $attribute.id), 'post'), ezhttp(concat($attribute_base, '_data_user_email_', $attribute.id), 'post'), cond($ngconnect_user.email|ends_with('@localhost.local')|not, $ngconnect_user.email|wash, ''))}" tabindex="2" />

			<label>{'Password'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="password" size="10" id="{$id_base}_password" name="{$attribute_base}_data_user_password_{$attribute.id}" value="" tabindex="3" />

			<label>{'Repeat password'|i18n('extension/ngconnect/ngconnect/profile')}</label><div class="labelbreak"></div>
			<input class="halfbox" type="password" size="10" id="{$id_base}_password_confirm" name="{$attribute_base}_data_user_password_confirm_{$attribute.id}" value="" tabindex="4" />
		</div>

		<div class="buttonblock">
			<input class="defaultbutton" type="submit" name="SaveButton" value="{'Save'|i18n('extension/ngconnect/ngconnect/profile')}" tabindex="5" />
			<input class="button" type="submit" name="SkipButton" value="{'Skip'|i18n('extension/ngconnect/ngconnect/profile')}" tabindex="7" />
			<input type="checkbox" tabindex="6" name="DontAskMeAgain" id="DontAskMeAgain" /><label for="DontAskMeAgain" style="display:inline;">{'Don\'t ask me again'|i18n('extension/ngconnect/ngconnect/profile')}</label>
		</div>
	</form>
</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>