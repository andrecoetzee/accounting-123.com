<?

$MODULE_MENUS = array(
	"business" => '
		<menu label="Hire">
		<menupopup>
			<menuitem label="New Hire" target="mainframe" value="hire/hire-invoice-new.php" />
			<menuitem label="View Hire" target="mainframe" value="hire/hire_view.php" />
			<menuitem label="View Hire Invoices" target="mainframe" value="hire/hire_nons_invoices_view.php" />
			<menuitem label="Reprint Hire Notes" target="mainframe" value="hire/hire_view_reprint.php" />
			<menu label="Bookings">
			<menupopup>
				<menuitem label="New Booking" target="mainframe" value="hire/booking_save.php" />
				<menuitem label="View Bookings" target="mainframe" value="hire/booking_view.php" />
			</menupopup>
			</menu>
			<menu label="Hire Reports">
			<menupopup>
				<menuitem label="Asset Report" target="mainframe" value="hire/asset_report.php" />
				<menuitem label="Hire Invoice Report" target="mainframe" value="hire/hire-invoices-report.php" />
				<menuitem label="Hire Detail Report" target="mainframe" value="hire/hire_detail_report.php" />
				<menuitem label="Overdue Hires Report" target="mainframe" value="hire/overdue_report.php" />
				<menuitem label="Hire History Report" target="mainframe" value="hire/hire_history_report.php" />
				<menuitem label="Hire Cash Report" target="mainframe" value="hire/hire_cashup.php" />
				<menuitem label="Signed Hire Notes" target="mainframe" value="hire/signed_hirenotes.php" />
				<menuitem label="Unsigned Hire Notes" target="mainframe" value="hire/unsigned_hirenotes.php" />
			</menupopup>
			</menu>
				<menu label="Hire Settings">
				<menupopup>
					<menuitem label="Customer Hire Basis" target="mainframe" value="hire/cust_basis.php" />
					<menuitem label="Default Hire Basis" target="mainframe" value="hire/default_basis.php" />
					<menuitem label="Service Settings" target="mainframe" value="hire/service_settings.php" />
					<menuitem label="Hire Note Comments" target="mainframe"
					value="hire/comment_settings.php" />
					<menuitem label="Contract Text" target="mainframe" value="hire/contract_text" />
					<menuitem label="Thank You Text" target="mainframe" value="hire/thanks_text_save.php" />
				</menupopup>
				</menu>
		</menupopup>
		</menu>'

);

?>
