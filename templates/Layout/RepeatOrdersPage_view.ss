<div id="Account">
	<div class="typography">
	<% if RepeatOrder %>
		<% control RepeatOrder %>
			<h2>Repeat Order #$ID ($Created.Long)</h2>

			<div id="PrintPageIcon">
				<img src="cms/images/pagination/record-print.png" onclick="window.print();">
			</div>

			<div class="block">
				<h3>Overview</h3>

				<% include RepeatOrder_Content %>

				<% if CanModify %>
				<div class="Actions">
					<input class="action" type="button" value="Make changes to your Repeat Order" onclick="window.location='$ModifyLink';" />
					<input class="action" type="button" value="Cancel your Repeat Order" onclick="window.location='$CancelLink';" />
					<input class="action" type="button" value="Done" onclick="window.location='$DoneLink';" />
				</div>
					<% end_if %>
			</div>

			<div class="clear"><!-- --></div>
		<% end_control %>
	<% else %>
		<p><strong>$Message</strong></p>
	<% end_if %>
	</div>
</div>
