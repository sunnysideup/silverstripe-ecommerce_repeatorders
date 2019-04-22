<% if $OrderItems %>
    <table id="InformationTable" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th scope="col" class="left">Products</th>
                <th scope="col" class="center"><% _t("QUANTITY", "Quantity") %></th>
            </tr>
        </thead>
    	<tbody>
            <% loop $OrderItems %>
        		<tr id="$IDForTable" class="$ClassForTable">
        			<td class="product title" scope="row">
        				<% if Link %>
        					<a href="$Link" title="<% sprintf(_t("READMORE","Click here to read more on &quot;%s&quot;"),$Title) %>">$BuyableTitle</a>
        				<% else %>
        					$BuyableTitle
        				<% end_if %>
                        $IDField
        			</td>
        			<td class="center quantity">$QuantityField</td>
        		</tr>
        	<% end_loop %>
    	</tbody>
    </table>
<% end_if %>
