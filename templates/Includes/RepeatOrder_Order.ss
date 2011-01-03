<table class="InformationTable" cellspacing="0" cellpadding="0">
	<tbody>
		<tr>
			<th scope="col" class="left">Products</th>
			<th scope="col" class="center"><% _t("QUANTITY", "Quantity") %></th>
		</tr>
		<% control Items %>
		<tr id="$IDForTable" class="$ClassForTable">
			<td class="product title" scope="row">
				<% if Link %>
					<a href="$Link" title="<% sprintf(_t("READMORE","Click here to read more on &quot;%s&quot;"),$Title) %>">$BuyableTitle</a>
				<% else %>
					$BuyableTitle
				<% end_if %>
			</td>
			<td class="center quantity">$Quantity</td>
		</tr>
		<% end_control %>
	</tbody>
</table>
