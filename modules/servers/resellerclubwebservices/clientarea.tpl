<h2 style="text-align: left;">Hosting Details</h2>
<table width="100%" cellspacing="0" cellpadding="0" class="frame" style="white-space: nowrap;">
    <tbody><tr>
            <td><table width="100%" border="0" cellpadding="10" cellspacing="0" style="white-space: nowrap;">
                    <tbody>
                        {if $is_processing}
                            <tr>
                                <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" class="fieldarea" width="30%" >Status:</td>
                                <td  style="overflow: hidden; text-overflow: ellipsis; text-align: left;" width="70%">{$wpp_status}</td>
                            </tr>
                        {else}
                            <tr>
                                <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" class="fieldarea" width="30%">Status:</td>
                                <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" width="70%">{$wpp_status}</td>
                            </tr>
                            <tr>
                                <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" class="fieldarea" width="30%">Control Panel:</td>
                                <td style="overflow: hidden; text-overflow: ellipsis ; text-align: left;" width="70%">
                                    <span style="display: inline-block;">{$wpp_websitebuilder_panel}</span>
                                    <span style="display: inline-block;">{$wpp_webhosting_panel}</span>
                                    <span style="display: inline-block;">{$wpp_mailhosting_panel}</span>
                                    <span style="display: inline-block;">{$wpp_dns_panel}</span>
                                    <span style="display: inline-block;">{$wpp_webmail_panel}</span>
                                </td>
                            </tr>
                            {if $is_websitebuilder}
                                <tr>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" class="fieldarea">Server IP Address:</td>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;">{$wpp_ip_address}</td>
                                </tr>
                                <tr>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" class="fieldarea">Web Disk Space</td>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;">{$wpp_diskspace}</td>
                                </tr>
                                <tr>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" class="fieldarea">Web Bandwidth</td>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;">{$wpp_bandwidth}</td>
                                </tr>
                            {/if}
                            <tr>
                                <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" class="fieldarea">Email Space</td>
                                <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;">{$wpp_allocated_mailspace}</td>
                            </tr>
                            <tr>
                                <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;" class="fieldarea">Email Accounts</td>
                                <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;">{$wpp_email_accounts}</td>
                            </tr>

                        {/if}
                    </tbody></table></td>
        </tr>
    </tbody></table>

{if $is_processing}
{else}
    <h2 style="text-align: left;">Name server Details</h2>
    <div style="text-align: left;"><h4>Option 1 : Use our Name Servers</h4></div>
    <table width="100%" cellspacing="0" cellpadding="0" class="frame" style="white-space: nowrap;font-size: 11px;">
        <tbody>
            <tr>
                <td>
                    <table width="100%" border="0" cellpadding="10" cellspacing="0" style="white-space: nowrap;">
                        <tbody>
                            {foreach from=$nameservers item=each_ns}

                                <tr>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;background-color: #F5F5F5;" class="fieldarea">Name server</td>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left;">{$each_ns}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <h2>OR</h2>
    <div style="text-align: left;"><h4>Option 2 : Add following DNS Records</h4></div>
    <table width="100%" cellspacing="0" cellpadding="0" class="frame" style="white-space: nowrap;font-size: 10px;">
        <tbody>
            <tr>
                <td>
                    <table width="100%" border="0" cellpadding="10" cellspacing="0" style="white-space: nowrap;  border-collapse: collapse;">
                        <tbody>
                            {foreach from=$dns_details item=each_dns}
                                <tr>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left; width: 20%;">{$each_dns.host}</td>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left; width: 10%;">{$each_dns.class}</td>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left; width: 10%;">{$each_dns.type}</td>
                                    <td style="overflow: hidden; text-overflow: ellipsis; text-align: left; width: 60%;">{$each_dns.value}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody></table>
    {/if}
<hr style="height: 2px;" />