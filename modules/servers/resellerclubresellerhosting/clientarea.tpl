<h2>Hosting Details</h2>
<table width="100%" cellspacing="0" cellpadding="0" class="frame">
    <tbody><tr>
            <td><table width="100%" border="0" cellpadding="10" cellspacing="0">
                    <tbody>
                        {if $is_processing}
                        <tr>
                            <td class="fieldarea" width="150">Status:</td>
                            <td>{$rh_status}</td>
                        </tr>
                        {else}
                        <tr>
                            <td class="fieldarea" width="150">Status:</td>
                            <td>{$rh_status}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Control Panel:</td>
                            <td>
                                <span style="display: inline-block;">{$rh_webhosting_panel}</span>
                                <span style="display: inline-block;">{$rh_whm_panel}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Temp URL for website:</td>
                            <td>{$rh_temp_url}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Control Panel URL:</td>
                            <td>{$rh_cp_url}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Username:</td>
                            <td>{$rh_cp_username}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Password:</td>
                            <td>{$rh_cp_password}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Server IP Address:</td>
                            <td>{$rh_ip_address}</td>
                        </tr>
                        {if $rh_whmcs_license_key}
                        <tr>
                            <td class="fieldarea">WHMCS License Key:</td>
                            <td>{$rh_whmcs_license_key}</td>
                        </tr>
                        {/if}
                        <tr>
                            <td class="fieldarea">NS1:</td>
                            <td>{$rh_dns_1}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">NS2:</td>
                            <td>{$rh_dns_2}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Web Disk Space</td>
                            <td>{$rh_diskspace}</td>
                        </tr>
                        <tr>
                            <td class="fieldarea">Web Bandwidth</td>
                            <td>{$rh_bandwidth}</td>
                        </tr>
                        {/if}
                    </tbody></table></td>
        </tr>
    </tbody></table>