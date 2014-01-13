{def $current_user = fetch( 'user', 'current_user' )}
{def $user_connections = fetch( 'ngconnect', 'connections', hash( 'user_id', $current_user.contentobject_id ) )}
{def $all_login_methods = ezini( 'ngconnect', 'LoginMethods', 'ngconnect.ini' )}
{def $current_networks = array()}

{if $user_connections}
    <p>{'Your account is currently linked to the following social networks'|i18n( 'extension/ngconnect/ngconnect/connections' )}:</p>

    <table cellspacing="0" cellpadding="4">
    <tr>
        <th>{'Social network name'|i18n( 'extension/ngconnect/ngconnect/connections' )}</th>
        <th></th>
    </tr>
    {foreach $user_connections as $connection sequence array( 'bglight', 'bgdark' ) as $seq}
        {set $current_networks = append( $connection.login_method )}
        <tr class="{$seq}">
            <td>{$connection.login_method|upfirst}</td>
            <td><a href={concat( 'ngconnect/unlink/', $connection.login_method )|ezurl()}>{'Unlink'|i18n( 'extension/ngconnect/ngconnect/connections' )}</a></td>
        </tr>
    {/foreach}
    </table>
{else}
    <p>{"Your account currently has no active social network connections."|i18n( 'extension/ngconnect/ngconnect/connections' )}</p>
{/if}

{def $additional_social_network_connections = false}

{foreach $all_login_methods as $l}
    {if $current_networks|contains( $l )|not()}
        {set $additional_social_network_connections = true}
    {/if}
{/foreach}

{if $additional_social_network_connections}
    <p>{"Additional social network connections are available"|i18n( 'extension/ngconnect/ngconnect/connections' )}:</p>
    {include uri="design:ngconnect/ngconnect.tpl"}
{/if}
