<table class="InformationTable" cellspacing="0" cellpadding="0">
	<tbody>
		<tr class="gap">
			<th colspan="4" scope="row" class="left">Details</th>
		</tr>
		<tr class="summary">
			<td scope="row" class="left">Member</td>
			<td>$Member.Title ($Member.Email)</td>
		</tr>
		<tr class="summary">
			<td scope="row" class="left">Status</td>
			<td>$TableStatus &nbsp;</td>
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
			<td scope="row" class="left">Delivery Day</td>
			<td>$TableDeliveryDay</td>
		</tr>
		<tr class="summary">
			<td scope="row" class="left">Notes</td>
			<td>$Notes</td>
		</tr>
		<tr>
			<th scope="col" class="left">Products</th>
			<th scope="col" class="center"><% _t("QUANTITY", "Quantity") %></th>
		</tr>
		<% control OrderItems %>
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
		<tr>
			<th scope="col" class="left">Alternatives</th>
			<th scope="col" class="center">&nbsp;</th>
		</tr>
		<% control TableAlternatives %>
		<tr class="summary">
			<td scope="row" class="left">$Title</td>
			<td>
				<% control Alternatives %>
				$Title <br />
				<% end_control %>
			</td>
		</tr>
		<% end_control %>
		<tr class="gap">
			<th colspan="4" scope="row" class="left">Schedule</th>
		</tr>
		<tr class="summary">
			<td scope="row" class="left">Planned Delivery Schedule</td>
			<td>$DeliverySchedule</td>
		</tr>
	</tbody>
</table>
