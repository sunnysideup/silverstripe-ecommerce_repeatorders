<% if CurrentMember  %>
    <div id="RepeatOrdersHolder">
        <h3><% _t("RepeatOrders.MySubscriptions","My Subscriptions") %></h3>
        <% if RepeatOrders %>
            <table>
                <thead>
                    <tr>
                        <th scope="col" class="left">Subscription</th>
                        <th scope="col" class="left">Status</th>
                        <th scope="col" class="right">Start Date</th>
                        <th scope="col" class="right">End Date</th>
                        <th scope="col" class="right">Frequency</th>
                    </tr>
                </thead>
                <tbody>
                    <% loop RepeatOrders %>
                        <tr>
                            <td class="left">
                                <a href="$Link">
                                    Subscription #{$ID}
                                </a><br>
                                created $Created.Nice
                            </td>
                            <td class="left">
                                $TableStatus
                            </td>
                            <td class="right">
                                $Start.Nice
                            </td>
                            <td class="right">
                                <% if $End %>
                                    $End.Nice
                                <% else %>
                                    Not Specified
                                <% end_if %>
                            </td>
                            <td class="right">
                                $Period
                            </td>
                        </tr>
                    <% end_loop %>
                </tbody>
            </table>
        <% else %>
                <% _t("RepeatOrders.NOREPEATORDERS","You don't currently have any subscriptions.") %>
        <% end_if %>
    </div>
<% else %>
    <% if MemberForm %>
        <div id="MemberForm">
            $MemberForm
        </div>
    <% end_if %>
<% end_if %>
