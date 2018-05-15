<style type="text/css">
	
	a, a:visited { 
		outline: none 
	}
	img {
		border: none;
	}
	
	/* dock - top */
	.dock {
		position: relative; 
		height: 80px; 
		text-align: center;
	}
	.dock-container {
		position: absolute;z-index:10;
		height: 47px;
		_height:50px;
		/*background: url(images/dock-bg2.gif);*/
		border:1px solid #EFEFEF;
		padding:0 10px 3px 0;
	}
	a.dock-item {
		display: block;
		width:40px;
		padding:0;
		margin:5px 5px 0 5px;
		color: #000;
		position: absolute;
		top: 0px;
		text-align: center;
		text-decoration: none!important;
		font: bold 12px Arial, Helvetica, sans-serif;
	}
	.dock-item img {
		border: none; 
		margin: 0 10px 0 0; 
		width:100%; 
	}
	.dock-item span {
		display: none; 
		padding:0;
		color:#999;
		text-decoration:none;
	}
	
	/* dock2 - bottom */
	#dock2 {
		width: 100%;
		bottom: 0px;
		position: absolute;
		left: 0px;
	}
	.dock-container2 {
		position: absolute;
		z-index:10;
		height: 50px;
		/*background: url(images/dock-bg.gif);*/
		border:1px solid #EFEFEF;
		padding-left: 20px;
	}
	a.dock-item2 {
		display: block; 
		font: bold 12px Arial, Helvetica, sans-serif;
		width: 40px; 
		color: #000; 
		bottom: 0px; 
		position: absolute;
		text-align: center;
		text-decoration: none;
	}
	.dock-item2 span {
		display: none;
		padding-left: 20px;
	}
	.dock-item2 img {
		border: none; 
		margin: 5px 10px 0px; 
		width: 100%; 
	}
</style>

<!--top dock --> 
<div class="dock" id="dock"> 
  <div class="dock-container"> 
	  	<a class="dock-item" href="<?=href('/?doc=admin/groups')?>"><img src="/repository/admin/images/admin-box-icon-groups.gif" alt="Groups"><span>Groups</span></a> 
	 	<a class="dock-item" href="<?=href('/?doc=admin/products')?>"><img src="/repository/admin/images/admin-box-icon-products.gif" alt="Products"><span>Products</span></a>
	 	<a class="dock-item" href="<?=href('/?doc=admin/polls')?>"><img src="/repository/admin/images/admin-box-icon-polls.gif" alt="Polls"><span>Polls</span></a>
	 	<a class="dock-item" href="<?=href('/?doc=admin/users')?>"><img src="/repository/admin/images/admin-box-icon-users.gif" alt="Users"><span>Users</span></a>
	 	<a class="dock-item" href="<?=href('/?doc=admin/newsletters')?>"><img src="/repository/admin/images/admin-box-icon-newsletters.gif" alt="Newsletters"><span>Newsletters</span></a>
	 	<a class="dock-item" href="<?=href('/?doc=admin/talkbacks')?>"><img src="/repository/admin/images/admin-box-icon-talkbacks.gif" alt="Talkbacks"><span>Talkbacks</span></a>
		<a class="dock-item" href="<?=href('/?doc=admin/shipping-methods')?>"><img src="/repository/admin/images/admin-box-icon-shipping.gif" alt="Shipping"><span>Shipping</span></a>
		<a class="dock-item" href="<?=href('/?doc=admin/orders')?>"><img src="/repository/admin/images/admin-box-icon-orders.gif" alt="Orders"><span>Orders</span></a>
		<a class="dock-item" href="http://www.google.com/analytics"><img src="/repository/admin/images/admin-box-icon-statistics.gif" alt="Statistics"><span>Statistics</span></a>
		<a class="dock-item" href="<?=href('/?doc=admin/verifone-tickets')?>"><img src="/repository/admin/images/admin-box-icon-tickets.gif" alt="Tickets"><span>Tickets</span></a>
		<a class="dock-item" href="<?=href('/?doc=admin')?>"><img src="/repository/admin/images/admin-box-icon-groups.gif" alt="Main"><span>Main</span></a>
  </div> 
</div>

<? /*
<!--bottom dock --> 
<div class="dock" id="dock2">
	<div class="dock-container2">
		<a class="dock-item2" href="#"><span>Groups</span><img src="/repository/admin/images/admin-box-icon-groups.gif" alt="Groups"></a> 
	 	<a class="dock-item2" href="#"><span>Products</span><img src="/repository/admin/images/admin-box-icon-products.gif" alt="Products"></a>
	 	<a class="dock-item2" href="#"><span>Polls</span><img src="/repository/admin/images/admin-box-icon-polls.gif" alt="Polls"></a>
	 	<a class="dock-item2" href="#"><span>Users</span><img src="/repository/admin/images/admin-box-icon-users.gif" alt="Users"></a>
	 	<a class="dock-item2" href="#"><span>Newsletters</span><img src="/repository/admin/images/admin-box-icon-newsletters.gif" alt="Newsletters"></a>
	 	<a class="dock-item2" href="#"><span>Talkbacks</span><img src="/repository/admin/images/admin-box-icon-talkbacks.gif" alt="Talkbacks"></a> 
	</div> 
</div> 
*/ ?>

<!--dock menu JS options -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script type="text/javascript" src="<?=href('/repository/include/javascript/interface.min.js')?>"></script>
<script type="text/javascript"> 
	
	$(document).ready(
		function()
		{
			$('#dock').Fisheye(
				{
					maxWidth: 40,
					items: 'a',
					itemsText: 'span',
					container: '.dock-container',
					itemWidth: 40,
					proximity: 80,
					halign : 'center'
				}
			)
			$('#dock2').Fisheye(
				{
					maxWidth: 60,
					items: 'a',
					itemsText: 'span',
					container: '.dock-container2',
					itemWidth: 40,
					proximity: 80,
					alignment : 'left',
					valign: 'bottom',
					halign : 'center'
				}
			)
		}
	);
 
</script> 