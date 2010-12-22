<?php

/* $Id$*/

//$PageSecurity = 2;

include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

/* Check that the config variable is set for
 * picking notes and get out if not.
 */
if ($_SESSION['RequirePickingNote']==0) {
	$title = _('Picking Lists Not Enabled');
	include('includes/header.inc');
	echo '<br>';
	prnMsg( _('The system is not configured for picking lists. Please consult your system administrator.'), 'info');
	include('includes/footer.inc');
	exit;
}

/* Show selection screen if we have no orders to work with */
if ((!isset($_GET['TransNo']) or $_GET['TransNo']=="") and !isset($_POST['TransDate'])){
	$title = _('Select Picking Lists');
	include('includes/header.inc');
	$sql='SELECT loccode,
				locationname
			FROM locations';
	$result=DB_query($sql, $db);
	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/sales.png" title="' . _('Search') . '" alt=""></img>' . ' ' . $title.'<br></p>';
	echo '<form action=' . $_SERVER['PHP_SELF'] . '?' . SID . ' method=post name="form">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection"><tr>';
	echo '<td>'._('Create picking lists for all deliveries to be made on').' : '.'</td>';
	echo '<td><input type=text class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="TransDate" maxlength=10 size=11 value='.date($_SESSION['DefaultDateFormat'], mktime(date('m'),date('Y'),date('d')+1)).'></td></tr>';
	echo '<tr><td>'._('From Warehouse').' : '.'</td><td><select name="loccode">';
	while ($myrow=DB_fetch_array($result)) {
		echo '<option value='.$myrow['loccode'].'>'.$myrow['locationname'].'</option>';
	}
	echo '</select></td></tr>';
	echo '</table>';
	echo "<br><div class='centre'><input type=submit name=Process value='" . _('Print Picking Lists') . "'></div></form>";
	include('includes/footer.inc');
	exit();
}

/*retrieve the order details from the database to print */
$ErrMsg = _('There was a problem retrieving the order header details from the database');

if (!isset($_POST['TransDate']) and $_GET['TransNo'] != 'Preview') {
/* If there is no transaction date set, then it must be for a single order */
	$sql = "SELECT salesorders.debtorno,
		salesorders.orderno,
		salesorders.customerref,
		salesorders.comments,
		salesorders.orddate,
		salesorders.deliverto,
		salesorders.deladd1,
		salesorders.deladd2,
		salesorders.deladd3,
		salesorders.deladd4,
		salesorders.deladd5,
		salesorders.deladd6,
		salesorders.deliverblind,
		salesorders.deliverydate,
		debtorsmaster.name,
		debtorsmaster.address1,
		debtorsmaster.address2,
		debtorsmaster.address3,
		debtorsmaster.address4,
		debtorsmaster.address5,
		debtorsmaster.address6,
		shippers.shippername,
		salesorders.printedpackingslip,
		salesorders.datepackingslipprinted,
		locations.locationname
	FROM salesorders,
		debtorsmaster,
		shippers,
		locations
	WHERE salesorders.debtorno=debtorsmaster.debtorno
	AND salesorders.shipvia=shippers.shipper_id
	AND salesorders.fromstkloc=locations.loccode
	AND salesorders.orderno='" . $_GET['TransNo']."'";
} else if (isset($_POST['TransDate']) or (isset($_GET['TransNo']) and $_GET['TransNo'] != 'Preview')) {
/* We are printing picking lists for all orders on a day */
	$sql = "SELECT salesorders.debtorno,
		salesorders.orderno,
		salesorders.customerref,
		salesorders.comments,
		salesorders.orddate,
		salesorders.deliverto,
		salesorders.deladd1,
		salesorders.deladd2,
		salesorders.deladd3,
		salesorders.deladd4,
		salesorders.deladd5,
		salesorders.deladd6,
		salesorders.deliverblind,
		salesorders.deliverydate,
		debtorsmaster.name,
		debtorsmaster.address1,
		debtorsmaster.address2,
		debtorsmaster.address3,
		debtorsmaster.address4,
		debtorsmaster.address5,
		debtorsmaster.address6,
		shippers.shippername,
		salesorders.printedpackingslip,
		salesorders.datepackingslipprinted,
		locations.locationname
	FROM salesorders,
		debtorsmaster,
		shippers,
		locations
	WHERE salesorders.debtorno=debtorsmaster.debtorno
	AND salesorders.shipvia=shippers.shipper_id
	AND salesorders.fromstkloc=locations.loccode
	AND salesorders.fromstkloc='".$_POST['loccode']."'
	AND salesorders.deliverydate='" . FormatDateForSQL($_POST['TransDate'])."'";
}

if (isset($_POST['TransDate']) or (isset($_GET['TransNo']) and $_GET['TransNo'] != 'Preview')) {
	$result=DB_query($sql,$db, $ErrMsg);

	/*if there are no rows, there's a problem. */
	if (DB_num_rows($result)==0){
		$title = _('Print Picking List Error');
		include('includes/header.inc');
		echo '<br>';
		prnMsg( _('Unable to Locate any orders for this criteria '), 'info');
		echo '<br><table class="selection"><tr><td>
				<a href="'. $rootpath . '/PDFPickingList.php?'. SID .'">' . _('Enter Another Date') . '</a>
				</td></tr></table><br>';
		include('includes/footer.inc');
		exit();
	}

	/*retrieve the order details from the database and place them in an array */
	$i=0;
	while ($myrow=DB_fetch_array($result)) {
		$OrdersToPick[$i]=$myrow;
		$i++;
	}
} else {
	$OrdersToPick[0]['debtorno']=str_pad('',10,'x');
	$OrdersToPick[0]['orderno']='Preview';
	$OrdersToPick[0]['customerref']=str_pad('',20,'x');
	$OrdersToPick[0]['comments']=str_pad('',100,'x');
	$OrdersToPick[0]['orddate']='1900-00-01';
	$OrdersToPick[0]['deliverto']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd1']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd2']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd3']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd4']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd5']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd6']=str_pad('',20,'x');
	$OrdersToPick[0]['deliverblind']=str_pad('',20,'x');
	$OrdersToPick[0]['deliverydate']='1900-00-01';
	$OrdersToPick[0]['name']=str_pad('',20,'x');
	$OrdersToPick[0]['address1']=str_pad('',20,'x');
	$OrdersToPick[0]['address2']=str_pad('',20,'x');
	$OrdersToPick[0]['address3']=str_pad('',20,'x');
	$OrdersToPick[0]['address4']=str_pad('',20,'x');
	$OrdersToPick[0]['address5']=str_pad('',20,'x');
	$OrdersToPick[0]['address6']=str_pad('',20,'x');
	$OrdersToPick[0]['shippername']=str_pad('',20,'x');
	$OrdersToPick[0]['printedpackingslip']=str_pad('',20,'x');
	$OrdersToPick[0]['datepackingslipprinted']='1900-00-01';
	$OrdersToPick[0]['locationname']=str_pad('',15,'x');
}
/* Then there's an order to print and its not been printed already (or its been flagged for reprinting/ge_Width=807;
)
LETS GO */

if ($OrdersToPick[0]['orderno']=='Preview') {
	$FormDesign = simplexml_load_file(sys_get_temp_dir().'/PickingList.xml');
} else {
	$FormDesign = simplexml_load_file($PathPrefix.'companies/'.$_SESSION['DatabaseName'].'/FormDesigns/PickingList.xml');
}

$PaperSize = $FormDesign->PaperSize;
include('includes/PDFStarter.php');
$pdf->addInfo('Title', _('Picking List') );
$pdf->addInfo('Subject', _('Laser Picking List') );
$FontSize=12;
$ListCount = 0; // UldisN
$Copy='';

$line_height=$FormDesign->LineHeight;

for ($i=0;$i<sizeof($OrdersToPick);$i++){
/*Cycle through each of the orders to pick */
	if ($i>0) {
		$pdf->newPage();
	}

	/* Now ... Has the order got any line items still outstanding to be picked */

	$PageNumber = 1;

	if (isset($_POST['TransDate']) or (isset($_GET['TransNo']) and $_GET['TransNo'] != 'Preview')) {
		$ErrMsg = _('There was a problem retrieving the order line details for Order Number') . ' ' .
			$OrdersToPick[$i]['orderno'] . ' ' . _('from the database');

		/* Are there any picking lists for this order already */
		$sql='SELECT COUNT(orderno)
				FROM pickinglists
				WHERE orderno='.$OrdersToPick[$i]['orderno'];
		$countresult=DB_query($sql, $db);
		$count=DB_fetch_row($countresult);
		if ($count[0]==0) {
		/* There are no previous picking lists for this order */
			$sql = "SELECT salesorderdetails.stkcode,
				stockmaster.description,
				salesorderdetails.orderlineno,
				salesorderdetails.quantity,
				salesorderdetails.qtyinvoiced,
				salesorderdetails.unitprice,
				salesorderdetails.narrative
			FROM salesorderdetails
			INNER JOIN stockmaster
				ON salesorderdetails.stkcode=stockmaster.stockid
			WHERE salesorderdetails.orderno='" . $OrdersToPick[$i]['orderno'] ."'";
		} else {
		/* There are previous picking lists for this order so
		 * need to take those quantities into account
		 */
			$sql = "SELECT salesorderdetails.stkcode,
				stockmaster.description,
				salesorderdetails.orderlineno,
				salesorderdetails.quantity,
				salesorderdetails.qtyinvoiced,
				SUM(pickinglistdetails.qtyexpected) as qtyexpected,
				SUM(pickinglistdetails.qtypicked) as qtypicked,
				salesorderdetails.unitprice,
				salesorderdetails.narrative
			FROM salesorderdetails
			INNER JOIN stockmaster
				ON salesorderdetails.stkcode=stockmaster.stockid
			LEFT JOIN pickinglists
				ON salesorderdetails.orderno=pickinglists.orderno
			LEFT JOIN pickinglistdetails
				ON pickinglists.pickinglistno=pickinglistdetails.pickinglistno
			WHERE salesorderdetails.orderno='" . $OrdersToPick[$i]['orderno'] ."'
			AND salesorderdetails.orderlineno=pickinglistdetails.orderlineno";
		}
		$lineresult=DB_query($sql,$db, $ErrMsg);
	}

	if ((isset($_GET['TransNo']) and $_GET['TransNo'] == 'Preview') or (isset($lineresult) and DB_num_rows($lineresult)>0)){
		/*Yes there are line items to start the ball rolling with a page header */
		include('includes/PDFPickingListHeader.inc');
		if (isset($_POST['TransDate']) or (isset($_GET['TransNo']) and $_GET['TransNo'] != 'Preview')) {
			$LinesToShow=DB_num_rows($lineresult);
			$PickingListNo = GetNextTransNo(19, $db);
			$sql='INSERT INTO pickinglists
				VALUES ('.
				$PickingListNo .','.
				$OrdersToPick[$i]['orderno'].',"'.
				FormatDateForSQL($_POST['TransDate']).'","'.
				date('Y-m-d').'",
				"0000-00-00")';
			$headerresult=DB_query($sql, $db);
		} else {
			$LinesToShow=1;
		}
		$YPos=$FormDesign->Data->y;
		$Lines=0;

		while ($Lines<$LinesToShow){
			if (isset($_GET['TransNo']) and $_GET['TransNo'] == 'Preview') {
				$myrow2['stkcode']=str_pad('',10,'x');
				$DisplayQty='XXXX.XX';
				$DisplayPrevDel='XXXX.XX';
				$DisplayQtySupplied='XXXX.XX';
				$myrow2['description']=str_pad('',18,'x');
				$myrow2['narrative']=str_pad('',18,'x');
				$itemdesc = $myrow2['description'] . ' - ' . $myrow2['narrative'];
			} else {
				$myrow2=DB_fetch_array($lineresult);
				if ($count[0]==0) {
					$myrow2['qtyexpected']=0;
					$myrow2['qtypicked']=0;
				}
				$DisplayQty = number_format($myrow2['quantity'],2);
				$DisplayPrevDel = number_format($myrow2['qtyinvoiced'],2);
				$DisplayQtySupplied = number_format($myrow2['quantity'] - $myrow2['qtyinvoiced']-$myrow2['qtyexpected']-$myrow2['qtypicked'],2);
				$itemdesc = $myrow2['description'] . ' - ' . $myrow2['narrative'];
				$sql='INSERT INTO pickinglistdetails
					VALUES('.
					$PickingListNo .','.
					$Lines.','.
					$myrow2['orderlineno'].','.
					$DisplayQtySupplied.',0)';
					$lineresult=DB_query($sql, $db);
			}
			$ListCount ++;

			$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column1->x,$Page_Height - $YPos,$FormDesign->Headings->Column1->Length,$FormDesign->Headings->Column1->FontSize,$myrow2['stkcode'],'left');
			$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column2->x,$Page_Height - $YPos,$FormDesign->Headings->Column2->Length,$FormDesign->Headings->Column2->FontSize,$itemdesc);
			$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column3->x,$Page_Height - $YPos,$FormDesign->Headings->Column3->Length,$FormDesign->Headings->Column3->FontSize,$DisplayQty,'right');
			$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column4->x,$Page_Height - $YPos,$FormDesign->Headings->Column4->Length,$FormDesign->Headings->Column4->FontSize,$DisplayQtySupplied,'right');
			$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column5->x,$Page_Height - $YPos,$FormDesign->Headings->Column5->Length,$FormDesign->Headings->Column5->FontSize,$DisplayPrevDel,'right');

			if ($Page_Height-$YPos-$line_height <= 50){
			/* We reached the end of the page so finsih off the page and start a newy */
				$PageNumber++;
				include ('includes/PDFPickingListHeader.inc');
			} //end if need a new page headed up
			else{
				/*increment a line down for the next line item */
				$YPos += ($line_height);
			}
			$Lines++;
		} //end while there are line items to print out

	} /*end if there are order details to show on the order*/
} /*end for loop to print the whole lot twice */

if ($ListCount == 0){
	$title = _('Print Picking List Error');
	include('includes/header.inc');
	include('includes/footer.inc');
	exit;
} else {
		$pdf->OutputD($_SESSION['DatabaseName'] . '_PickingLists_' . date('Y-m-d') . '.pdf');//UldisN
		$pdf->__destruct(); //UldisN
}
?>