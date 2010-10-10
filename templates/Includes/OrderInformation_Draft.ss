<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<% base_tag %>
		<style type="text/css">
			html,body{
				text-align:left;
				border:0;
				padding:0;
				margin:0;
				background:#e9e9e9;
			}
			body {
				font-size:62.5%;
			}
			* {
				font-size:1em;
			}
			h1 {
				font-size:2em;
			}
			h2 {
				font-size:1.5em;
			}
			#OrderInformation,
			#OrderStatus {
				margin:20px;
				background:#fff;
				padding:20px;
				border:1px solid #333;
				font-family:Verdana,Arial,Helvetica,sans-serif;
				width:460px;
			}

			#OrderStatus label.left {
				width:100px;
				float:left;
			}

		/* Information table styling */
		.InformationTable {
			border-top: 1px solid #ccc;
			border-bottom: 1px solid #ccc;
			background: #fdfdfd;
		}

			.InformationTable tr.Total {
				background: #c9ebff;
			}

			/* apply the colour to these elements */
			.InformationTable tr.Total td,
			.InformationTable th {
				color: #4EA3D7 !important;
				font-weight: bold;
			}
				.warningMessage {
					padding: 5px;
					width: 97%;
					color: #DC1313;
					border: 1px solid #FF7373;
					background: #FED0D0;
				}

				.successMessage {
					padding: 5px;
					width: 97%;
					border: 2px solid #00FF00;
					background: #B9FFB9;
				}

			/* total line in order information table */
			.InformationTable tr.Total td {
				text-transform: uppercase;
			}

			.InformationTable tr.summary {
				font-weight: bold;
			}
				.InformationTable tr td,
				.InformationTable tr th {
					padding: 5px;
					font-size: 1.2em;
					color: #333;
				}
					.InformationTable td.product {
						width: 30%;
					}
					.InformationTable td.ordersummary {
						font-size: 1em;
						border-bottom: 1px solid #ccc;
					}
					.InformationTable tr td a {
						color: #666;
					}
						#InformationTable tr td a img {
							vertical-align: middle;
						}

			/* Information table alignment classes */
			.InformationTable .right {
				text-align: right;
			}
			.InformationTable .center {
				text-align: center;
			}
			.InformationTable .left {
				text-align: left;
			}

		</style>
		<script type="text/javascript">
			if(document.location.href.indexOf('print=1') > 0) {
				window.print();
			}
		</script>
		<title><% _t("PAGETITLE","Print Orders") %></title>
		</head>
		<body>

				<div id="OrderInformation">

					<h1 class="pageTitle">Draft Order</h1>

					<% if Published %>
						<p class="successMessage"><strong>Your order has been published successfully</strong> <a onclick="javascript:window.top.GB_hide(); return false;" href="#">(Close Popup)</a></p>
					<% end_if %>

					<% if Published %><% else %>
						<div class="Actions"><input onclick="window.location = '$PublishLink'" type="button" title="Publish this Order" value="Publish this Order" class="action"/></div>
					<% end_if %>

					<h2>Repeat Order Information</h2>
					<% control RepeatOrder %>
						<% include OrderInformation_RepeatOrder %>
					<% end_control %>

					<% if Published %><% else %>
						<div class="Actions"><input onclick="window.location = '$PublishLink'" type="button" title="Publish this Order" value="Publish this Order" class="action"/></div>
					<% end_if %>

					<h2>Draft Order Information</h2>
					<p>Modifiers will not be added until after the Order is Publsihed</p>
					<% control Order %>
						<% include OrderInformation_AutomaticallyCreatedOrder %>
					<% end_control %>

					<% if Published %><% else %>
						<div class="Actions"><input onclick="window.location = '$PublishLink'" type="button" title="Publish this Order" value="Publish this Order" class="action"/></div>
					<% end_if %>
				</div>
		</body>
</html>
