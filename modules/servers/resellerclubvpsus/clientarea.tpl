<h2>Hosting Details</h2>
<table width="100%" cellspacing="0" cellpadding="0" class="frame">
    <tbody><tr>
            <td><table width="100%" border="0" cellpadding="10" cellspacing="0">
                    <tbody>
                        {if $is_processing}
                            <tr>
                                <td class="fieldarea" width="150">Status:</td>
                                <td>{$vps_status}</td>
                            </tr>
                        {else}
                            <tr>
                                <td class="fieldarea" width="150">Status:</td>
                                <td>{$vps_status}</td>
                            </tr>                        
                            <!--                        <tr>
                                                        <td class="fieldarea">Control Panel:</td>
                                                        <td>
                                                            
                                                            <span style="display: inline-block;">{$vps_mailhosting_panel}</span>
                                                            <span style="display: inline-block;">{$vps_dns_panel}</span>
                                                            <span style="display: inline-block;">{$vps_webmail_panel}</span>
                                                        </td>
                                                    </tr>-->
                            {if $is_whmcs_enabled}
                                <tr>
                                    <td class="fieldarea">WHMCS Licence Key</td>
                                    <td>{$vps_whmcs_key}</td>
                                </tr>
                            {/if}
                            <tr>
                                <td class="fieldarea">SSL Ip address</td>
                                <td>{$vps_ssl_ip_address}</td>
                            </tr>

                            <tr>
                                <td class="fieldarea">Virtuozzo Power Panel (VZ) URL:</td>
                                <td>{$vps_virtuozzo_url} <span style="display: inline-block;">{$vps_webhosting_panel}</span></td></td>
                            </tr>
                            <tr>
                                <td class="fieldarea">Virtuozzo Panel Username:</td>
                                <td>{$vps_virtuozzo_username}</td>
                            </tr>
                            {if $is_cpanel_enabled}
                                <tr>
                                    <td class="fieldarea">CPanel Login URL:</td>
                                    <td>{$vps_cp_url} <span style="display: inline-block;">{$vps_webhosting_panel}</span></td>
                                </tr>
                                <tr>
                                    <td class="fieldarea">Cpanel Username:</td>
                                    <td>{$vps_cp_username}</td>
                                </tr>
                                <tr>
                                    <td class="fieldarea">Password:</td>
                                    <td>{$vps_cp_password}</td>
                                </tr>
                            {/if}
                            <!--                            <tr>
                                                            <td class="fieldarea">Server IP Address:</td>
                                                            <td>{$vps_ip_address}</td>
                                                        </tr>-->
                            <tr>
                                <td class="fieldarea">Web Disk Space</td>
                                <td>{$vps_diskspace}</td>
                            </tr>
                            <tr>
                                <td class="fieldarea">Web Bandwidth</td>
                                <td>{$vps_bandwidth}</td>
                            </tr>                            
                            <tr>
                                <td class="fieldarea">Server processor</td>
                                <td>{$vps_cpu}</td>
                            </tr>                            
                            <tr>
                                <td class="fieldarea">Server Memory</td>
                                <td>{$vps_ram}</td>
                            </tr>                            
                        {/if}
                    </tbody></table></td>
        </tr>
    </tbody></table>