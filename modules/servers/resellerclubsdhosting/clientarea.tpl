<h2>Hosting Details</h2>
<table width="100%" cellspacing="0" cellpadding="0" class="frame">
    <tbody><tr>
            <td><table width="100%" border="0" cellpadding="10" cellspacing="0">
                    <tbody>
                        {if $is_processing}
                        <tr>
                            <td class="fieldarea" width="150">Status:</td>
                            <td>{$sdh_status}</td>
                        </tr>
                        {else}
                        <tr>
                            <td class="fieldarea" width="150">Status:</td>
                            <td>{$sdh_status}</td>
                        </tr>                        
                        <tr>
                            <td class="fieldarea">Control Panel:</td>
                            <td>
                                <span style="display: inline-block;">{$sdh_webhosting_panel}</span>
                                <span style="display: inline-block;">{$sdh_mailhosting_panel}</span>
                                <span style="display: inline-block;">{$sdh_dns_panel}</span>
                                <span style="display: inline-block;">{$sdh_webmail_panel}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Temp URL for website:</td>
                            <td>{$sdh_temp_url}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Control Panel URL:</td>
                            <td>{$sdh_cp_url}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Username:</td>
                            <td>{$sdh_cp_username}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Password:</td>
                            <td>{$sdh_cp_password}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Server IP Address:</td>
                            <td>{$sdh_ip_address}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Web Disk Space</td>
                            <td>{$sdh_diskspace}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Web Bandwidth</td>
                            <td>{$sdh_bandwidth}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">POP accounts count:</td>
                            <td>{$sdh_mailpop}</td>
                        </tr>                        
                        <tr>
                            <td class="fieldarea">Allocated mail space:</td>
                            <td>{$sdh_allocated_mailspace}</td>
                        </tr> 
                        {/if}
                    </tbody></table></td>
        </tr>
    </tbody></table>