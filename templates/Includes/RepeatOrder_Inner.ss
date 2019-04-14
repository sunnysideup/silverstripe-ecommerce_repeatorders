<tr class="summary">
    <td scope="row" class="left">Status</td>
    <td>$TableStatus</td>
</tr>
<tr class="summary">
    <td scope="row" class="left">Payment Method</td>
    <td>$TablePaymentMethod</td>
</tr>
<tr class="summary">
    <td scope="row" class="left">Start</td>
    <td>$Start.Long</td>
</tr>
<tr class="summary">
    <td scope="row" class="left">End</td>
    <td>$End.Long</td>
</tr>
<tr class="summary">
    <td scope="row" class="left">Period</td>
    <td>$Period</td>
</tr>
<tr class="summary">
    <td scope="row" class="left">Notes</td>
    <td>$Notes</td>
</tr>
<tr>
    <th scope="col" class="left">Products</th>
    <th scope="col"><% _t("QUANTITY", "Quantity") %></th>
</tr>
<% control OrderItems %>
<tr id="$IDForTable" class="$ClassForTable">
    <td class="product title" scope="row">
        <% if Link %>
            <a href="$Link" title="<% sprintf(_t("READMORE","Click here to read more on &quot;%s&quot;"),$Title) %>">$BuyableTitle</a>
        <% else %>
            $BuyableTitle
        <% end_if %>
<% if AlternativesPerProduct %>
        <ul>
        <% control AlternativesPerProduct %>
        <li><a href="$Link">$Title</a></li>
        <% end_control %>
        </ul>
<% end_if %>
    </td>
    <td class="center quantity">$Quantity</td>
</tr>
<% end_control %>
<tr class="gap">
    <th colspan="4" scope="row" class="left">Schedule</th>
</tr>
<tr class="summary">
    <td scope="row" class="left">Planned Delivery Schedule</td>
    <td><% if DeliverySchedule %>$DeliverySchedule<% else %>No planned deliveries<% end_if %></td>
</tr>
