<% if AllRepeatOrders %>
<h2>Instructions</h2>
<p>
	Click on the individual orders below to complete them (<em>complete now</em> link).
	The orders will open in a separate window/tab.
	Complete the order at hand and then come back to this page to complete the next order.
</p>
<p>
	The colour scheme shows you if the individual orders are to be completed in the:
</p>
<ul class="TestDraftOrderList">
	<li class="past">past: should have been completed before today</li>
	<li class="current">current: to be completed today</li>
	<li class="future">future: to be completed some time in the future</li>
</ul>

<h2>Display Options</h2>
<ul class="RepeatOrdersGeneralOptions">
	<li><a href="" class="showHideAllRepeatOrders">show all details for each Repeat order</a> - details are hidden by default, you can click on individual Repeat order title to view its details</li>
	<li><a href="" class="showHideWithoutOutRepeat">show Repeat orders without outRepeat orders</a> - these are hidden by default</li>
	<li><a href="" class="showHideFutureOrders">show future orders</a> - these are hidden by default</li>
</ul>
<h2>Repeat Orders</h2>
<ul class="RepeatOrderList">
<% else %>
<p>There are currently no <em>active</em> Repeat orders. If you were expecting some active orders here then please make sure they are <a href="/admin/Repeat-orders">made <em>active</em></a> in the CMS first.</p>
<% end_if %>
