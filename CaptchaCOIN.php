<?php
/*
Plugin Name: WP Bitcoin Wallet
Plugin URI: https://www.captchaco.in/
Description: The CaptchaCO.IN Bitcoin Wallet is a secure cryptocurrency (BTC) wallet that allows you to send and receive bitcoins in your blog anytime and anywhere.
Author: gprialde
Version: 1.9
Author URI: https://www.captchaco.in/
License: GPLv2 or later
*/

require_once('ChangerAPI.php');

class CaptchaCOIN {
	public function __construct() {
		add_action('admin_menu', array($this, 'admin_page'));
		add_action('init',array($this, 'captchacoin_wallet_install'));			
		
		add_filter( 'wp_nav_menu_args', array($this, 'wpesc_nav_menu_args' ));
		add_filter( 'wp_page_menu_args', array($this, 'wpesc_nav_menu_args' ));
		add_filter( 'wp_list_pages_excludes', array($this, 'wpesc_nav_menu_args'));					
	}	

	public function captchacoin_wallet_install() {
		global $wpdb;
		$captchacoin_db_version = '1.0';

		$table_name = $wpdb->prefix . 'captchacoin_exchanges';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (`id` INT NOT NULL AUTO_INCREMENT , `exchange_id` VARCHAR(255) NOT NULL , `send_amount` DOUBLE NOT NULL DEFAULT '0' , `rate` DOUBLE NOT NULL DEFAULT '0' , `receive_amount` DOUBLE NOT NULL DEFAULT '0' , `receiver_id` VARCHAR(255) NOT NULL , `payee` VARCHAR(255) NOT NULL , `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`)) ENGINE = MyISAM;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'captchacoin_db_version', $captchacoin_db_version );
	}	

    //wp-admin options
	public function admin_page() {
	     add_menu_page('Bitcoin Wallet', 'Bitcoin Wallet', 'add_users', 'wp_bitcoin_wallet',  array($this, 'wallet_page'), plugins_url('wp-bitcoin-wallet/favicon.ico'));
		 add_submenu_page( 'wp_bitcoin_wallet', 'USD Cashout', 'USD Cashout', 'add_users', 'wp_bitcoin_wallet_exchange', array($this, 'bitcoin_wallet_exchange'));
		 add_submenu_page( 'wp_bitcoin_wallet', 'Buy Bitcoins', 'Buy Bitcoins', 'add_users', 'wp_bitcoin_wallet_exchange', array($this, 'buy_bitcoin_exchange'));
	}

	public function buy_bitcoin_exchange() {
		wp_redirect( 'https://www.captchaco.in/buy/', 301 ); 
		exit;
	}
	
	public function wallet_page() {		
	?>
	
	<div class="wrap">
		<h3><img src="https://www.captchaco.in/favicon.ico" align="left" style="display: inline; float: left; margin-right: 5px;">CaptchaCO.IN Bitcoin Wallet</h3>		
		<div style="clear:both;width:500px;border-top:1px solid #EFEFEF;margin-top:10px;margin-bottom:10px;"></div>
	<?
	//update options	
	if(isset($_POST['sb_captchacoin'])) {		
		update_option('wp_bitcoin_wallet_key', $_POST['wp_bitcoin_wallet_key']);
		echo '<div class="updated bellow-h2" style="margin-left: 0px; padding: 10px; color: #00CC00;">Success! Your CaptchaCO.IN Application Key Has Been Saved...</div>';
	}
	
	$wp_bitcoin_wallet_key = get_option('wp_bitcoin_wallet_key');		
	?>
		<form method="POST" enctype="multipart/form-data">			
			<table style="padding-left: 0; margin-left: 0;">
				<tr>
					<td>CaptchaCO.IN Application Key</td>
					<td>
						<input type="text" name="wp_bitcoin_wallet_key" value="<?=$wp_bitcoin_wallet_key?>" style="width: 250px;" />						
					</td>
					<td><input type="submit" name="sb_captchacoin" value="Save My Application Key" class="button button-primary"/></td>
					<td>[You can get your own key at <a href="https://www.captchaco.in/" target="_blank">https://www.captchaco.in/</a>]</td>
				</tr>
			</table>			
		</form>	
	
		<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>		
		
		<? 	
			$res_wallet = file_get_contents("https://www.captchaco.in/api/?key=" . $wp_bitcoin_wallet_key . "&proc=get_wallet_address");
			$wallet = json_decode($res_wallet, true);
		
		if (!empty($wallet['address'])) {			
			$res_balance = file_get_contents("https://www.captchaco.in/api/?key=" . $wp_bitcoin_wallet_key . "&proc=get_wallet_balance");
			$balance = json_decode($res_balance, true);	
			$balance = $balance['balance'];
	
			$res_fiat = file_get_contents("https://www.captchaco.in/api/?key=" . $wp_bitcoin_wallet_key . "&proc=get_btc_usd");
			$fiat = json_decode($res_fiat, true);
			$fiat = $fiat['usd'];
		?>
		
		Current USD Fiat Exchange Rate: $<?=$fiat?> USD / 1 BTC		
		<div style="float: right;">[<a href="https://www.captchaco.in/buy/" target="_blank" style="text-decoration: none;">BUY BITCOINS INSTANTLY</a>]</div>
		<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
		
		<table cellspacing="10" border="0">
			<tr>
				<td align="right" style="font-size: 18px;"><b>Bitcoin Balance</b>:</td><td align="left" style="font-size: 18px;"><span id="balance" style="color: #36a97f; font-weight: normal;"><?=number_format(floatval($balance), 8)?></span> BTC / $<span id="fiat" style="color: #36a97f; font-weight: normal;"><?=number_format((floatval($balance) * $fiat), 2)?></span> USD</td>
			</tr>	
			<tr>			
				<td align="right" style="font-size: 18px;"><b>Wallet Address</b>:</td><td align="left" style="font-size: 18px;"><a href="https://btc.com/<?=$wallet['address']?>" target="_blank"><?=$wallet['address']?></a></td>
			</tr>
			<tr>			
				<td align="right" style="font-size: 18px; font-weight: italic;"><i>Pending:</i></td><td align="left" style="font-size: 18px;"><span id="pending" style="font-size: 18px; font-style: italic;">0 BTC</span></td>
			</tr>
		</table>

		<script>
			setInterval(function(){loadJSON();loadBalance();}, 10000);
			function loadJSON() {
			  var xhttp = new XMLHttpRequest();
			  xhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {				 
				 var t = JSON.parse(this.responseText);					 
				 document.getElementById("pending").innerHTML = t.pending + " BTC";				 
				} else {
				 document.getElementById("pending").innerHTML = "Loading...";
				}
			  };
			  xhttp.open("GET", "https://www.captchaco.in/api/?key=<?=$wp_bitcoin_wallet_key?>&proc=get_wallet_pending", true);
			  xhttp.send();
			};	
			function loadBalance() {
			  var xhttp = new XMLHttpRequest();
			  xhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {				 
				 var t = JSON.parse(this.responseText);					 
				 document.getElementById("balance").innerHTML = t.balance;
				 document.getElementById("fiat").innerHTML = (t.balance * <?=$fiat?>).toFixed(2);
				} else {
				 document.getElementById("balance").innerHTML = "Loading...";
				 document.getElementById("fiat").innerHTML = "Loading...";
				}
			  };
			  xhttp.open("GET", "https://www.captchaco.in/api/?key=<?=$wp_bitcoin_wallet_key?>&proc=get_wallet_balance", true);
			  xhttp.send();
			};				
		</script>
		
		<?
			if(isset($_POST['send'])) {		
				if (!empty($_POST['to']) && !empty($_POST['amount'])) {
					$res_send = file_get_contents("https://www.captchaco.in/api/?key=" . $wp_bitcoin_wallet_key . "&proc=send_bitcoins&args=" . $_POST['amount'] . "," . $_POST['to']);
					$send = json_decode($res_send, true);
					
					$error = $send['error'];
					$msg = $send['msg'];
					
					if ($error != "true") {
						echo '<div class="updated" style="margin-left: 0px; padding: 10px; color: #00CC00;">Success! Bitcoins sent, check <a href="https://blockchain.info/tx/' . $msg . '">' . $msg . '</a> now.</div>';	
					} else {
						echo '<div class="error" style="margin-left: 0px; padding: 10px; color: #CC0000;">Error: ' . $msg . '...</div>';
					}					
				} else {
					echo '<div class="error" style="margin-left: 0px; padding: 10px; color: #CC0000;">Error: Both TO and AMOUNT fields are required and should not be empty!</div>';					
				}				
			}		
		?>
		
		<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
		SEND BITCOINS
		<div style="float: right;">[<a href="https://www.captchaco.in/buy/" target="_blank" style="text-decoration: none;">BUY BITCOINS INSTANTLY</a>]</div>
		<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
		
		<form method="POST">		
			<table cellspacing="10" border="0">
				<tr>
					<td align="right"><b>TO</b>:</td><td align="left" style="font-size: 18px;"><input type="text" name="to" size="40"></td>
				</tr>	
				<tr>			
					<td align="right"><b>AMOUNT</b>:</td><td align="left" style="font-size: 18px;"><input type="text" name="amount" size="40"><br><small style="font-size: 12px;">BTC amount + 0.0001 network fee.</small></td>
				</tr>
				<tr>			
					<td align="right"></td><td align="left" style="font-size: 18px;"><input type="submit" name="send" value="Send Bitcoins" class="button button-primary"></td>
				</tr>				
			</table>
		</form>
		
		<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
		MOST RECENT TRANSACTIONS
		<div style="float: right;">[<a href="https://www.captchaco.in/buy/" target="_blank" style="text-decoration: none;">BUY BITCOINS INSTANTLY</a>]</div>
		<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
		
		<? 	
			$res_transactions = file_get_contents("https://www.captchaco.in/api/?key=" . $wp_bitcoin_wallet_key . "&proc=get_wallet_transactions");
			$transactions = json_decode($res_transactions, true);
		?>
		
			<table cellspacing="0" cellpadding="0" width="100%" class="wp-list-table widefat posts">
				<thead> 
					<tr>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>Date</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>Address</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>Type</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>Amount</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>Fee</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>Confirmations</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>Info</strong></th> 
					</tr>
				</thead>
				<tbody>
				   <?php
				   $bold_txxs = "";
				   $p = 0;
				   $transactions = array_reverse($transactions);
				   foreach($transactions as $btc_transaction) {
					  if (!empty($btc_transaction['txid'])){
						  if ($p % 2 == 0) { $b = "background-color: #FFF;"; } else { $b = "background-color: #DDDDDD;"; }
						  if($bold_txxs=="") { $bold_txxs = "color: #666666; "; } else { $bold_txxs = ""; }								  
						  if($btc_transaction['category']=="send") { $tx_type = '<b style="color: #FF0000;">Sent</b>'; } else { $tx_type = '<b style="color: #36a97f;">Received</b>'; }
						  if ($btc_transaction['category']!="send" && $btc_transaction['confirmations'] == 0) { $tx_type = '<b style="color: #0000FF;">Pending</b>'; }									  
						  echo '<tr>
								   <td align="left" style="'.$bold_txxs.'padding: 7px; ' . $b . '" nowrap>'.date('n/j/Y h:i a',$btc_transaction['time']).'</td>
								   <td align="left" style="'.$bold_txxs.'padding: 7px; ' . $b . '" nowrap><a href="https://btc.com/'.$btc_transaction['address'].'" target="_blank">'.$btc_transaction['address'].'</a></td>
								   <td align="right" style="'.$bold_txxs.'padding: 7px; ' . $b . '" nowrap>'.$tx_type.'</td>
								   <td align="right" style="'.$bold_txxs.'padding: 7px; ' . $b . '" nowrap>'.abs($btc_transaction['amount']).' BTC</td>
								   <td align="right" style="'.$bold_txxs.'padding: 7px; ' . $b . '" nowrap>'.abs($btc_transaction['fee']).'</td>
								   <td align="right" style="'.$bold_txxs.'padding: 7px; ' . $b . '" nowrap>'.$btc_transaction['confirmations'].'</td>
								   <td align="center" style="padding: 7px; ' . $b . '" nowrap><a href="https://blockchain.info/tx/'.$btc_transaction['txid'].'" target="_blank" rel="tooltip" title="Transaction Block Information">Transaction Information</a></td>
								</tr>';
						  $p++;
					  }
				   }
				   ?>
				</tbody>						
			</table>
		
		<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
		
		<? } ?>								
		
		<div style="color: #555555;">We Accept <a href="https://www.coinbase.com/checkouts/9af1594677125214ee9473a219c16c4f" target="_blank">Bitcoin Donations</a></div>
	</div>
<?php
	}
	
	public function bitcoin_wallet_exchange() {			
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'captchacoin_exchanges';
		
		$wp_bitcoin_wallet_key = get_option('wp_bitcoin_wallet_key');

		$res_balance = file_get_contents("https://www.captchaco.in/api/?key=" . $wp_bitcoin_wallet_key . "&proc=get_wallet_balance");
		$balance = json_decode($res_balance, true);	
		$balance = $balance['balance'];		
		
		$timestamp = time();
		
		$API_Auth = new ChangerAuth('bad3df70-7f1b-4324-b7c7-38be32cb5775', 'dac73da45e16f3345cb5922f7778769ae1f8ad830c92e4cb762486e8504f3ae5', $timestamp);
		$Changer_API = new ChangerAPI($API_Auth);
		
		$rate = $Changer_API->getRate("bitcoin_BTC", "pm_USD");		
		$limits = $Changer_API->getLimits("bitcoin_BTC", "pm_USD");		
?>
		<div class="wrap">
			<h3><img src="https://www.captchaco.in/favicon.ico" align="left" style="display: inline; float: left; margin-right: 5px;">CaptchaCO.IN Bitcoin Exchange</h3>		
			<div style="clear:both;width:500px;border-top:1px solid #EFEFEF;margin-top:10px;margin-bottom:10px;"></div>		
						
			<p style="float: left;">Current Cashout Exchange Rate: $<?=number_format($rate['rate'], 2)?> USD</p>
			<p style="float: right;">Exchange Limits: Minimum = <?=$limits['limits']['min_amount']?> BTC and Maximum = <?=$limits['limits']['max_amount']?> BTC</p>
			<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
			
<?
			if(isset($_POST['exchange'])) {		
			
				if (!empty($_POST['email']) && !empty($_POST['amount']) && !empty($_POST['receiver']) && !empty($_POST['receive'])) {
					$balance_s = $_POST['amount'] + 0.0001;
					if ($balance >= $balance_s) {
						$order = array(
							'email' => $_POST['email'], // public mode
							'send' => "bitcoin_BTC",
							'refid' => $_POST['refid'],
							'receive' => $_POST['receive'],
							'amount' => $_POST['amount'],
							'receiver_id' => $_POST['receiver']
						);
						
						try {
							$makeExchange = $Changer_API->makeExchange($order);
							
							if($makeExchange['receiver_id'] != $order['receiver_id']) {
								echo '<div class="error" style="margin-left: 0px; padding: 10px; color: #CC0000;">Error: Receiver ID does not match...</div>';
							} else {
								$wpdb->insert( 
									$table_name, 
									array( 
										'exchange_id' => $makeExchange['exchange_id'], 
										'send_amount' => $makeExchange['send_amount'], 
										'rate' => $makeExchange['rate'],
										'receive_amount' => $makeExchange['receive_amount'],
										'receiver_id' => $makeExchange['receiver_id'],
										'payee' => $makeExchange['payee']
									) 
								);
								
								$res_send = file_get_contents("https://www.captchaco.in/api/?key=" . $wp_bitcoin_wallet_key . "&proc=send_bitcoins&args=" . $makeExchange['send_amount'] . "," . $makeExchange['payee']);
								$send = json_decode($res_send, true);
								
								$error = $send['error'];
								$msg = $send['msg'];								
								
								if ($error != 'true') {
									echo '<div class="updated bellow-h2" style="margin-left: 0px; padding: 10px; color: #00CC00;">Success! Exchange details has been emailed to you. Your exchange ID is ' . $makeExchange['exchange_id'] . '</div>';
								} else {
									echo '<div class="error" style="margin-left: 0px; padding: 10px; color: #CC0000;">Error: ' . $msg . '...</div>';
								}								
							} 						
						} catch (Exception $e) {
							echo '<div class="error" style="margin-left: 0px; padding: 10px; color: #CC0000;">Error: ' . $e->getMessage() . '...</div>';
						}						
					} else {
						echo '<div class="error" style="margin-left: 0px; padding: 10px; color: #CC0000;">Error: Bitcoin balance is not enough to make a send request. Please take note that a fee of 0.0001 BTC is added to your send amount...</div>';
					}
				} else {
					echo '<div class="error" style="margin-left: 0px; padding: 10px; color: #CC0000;">Error: All fields must not be empty...</div>';
				}				
			}					
?>			
			<form method="POST">		
				<table cellspacing="10" border="0">
					<tr>			
						<td align="right"><b>GATEWAY</b> (USD):</td>
						<td align="left" style="font-size: 18px;">
							<select name="receive">
								<option value=""></option>
								<option value="payeer_USD">Payeer USD</option>
								<option value="advcash_USD">Advcash USD</option>
							</select>
						</td>
					</tr>				
					<tr>
						<td align="right"><b>EMAIL</b>:</td><td align="left" style="font-size: 18px;"><input type="text" name="email" size="40"><input type="hidden" name="refid" value="106932" size="40"></td>
					</tr>				
					<tr>
						<td align="right"><b>BITCOINS</b> (BTC):</td><td align="left" style="font-size: 18px;"><input type="text" name="amount" size="40"><br><small style="font-size: 12px;">BTC amount + 0.0001 network fee.</small></td>
					</tr>	
					<tr>
						<td align="right"><b>ACCOUNT / ADDRESS</b>:</td><td align="left" style="font-size: 18px;"><input type="text" name="receiver" size="40"><br><small style="font-size: 12px;">The account/address the exchange will be processed to.<br>Make sure it belongs to you: transactions are irreversible.</small></td>
					</tr>					
					<tr>			
						<td align="right"></td><td align="left" style="font-size: 18px;"><input type="submit" name="exchange" value="Exchange My Bitcoins" class="button button-primary"></td>
					</tr>				
				</table>
			</form>
		
			<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
			MY EXCHANGES
			<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
		
			<table cellspacing="0" cellpadding="0" width="100%" class="wp-list-table widefat posts">
				<thead> 
					<tr>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>ID</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>EXCHANGE ID</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>SEND AMOUNT</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>RATE</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>RECEIVE AMOUNT</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>STATUS</strong></th>
						<th style="background-color: #C0C0C0; padding: 7px; text-align: center;"><strong>ORDERED</strong></th>
					</tr>
				</thead>
				<tbody>
				   <?php
					   $exchanges = $wpdb->get_results("SELECT * FROM $table_name ORDER BY `created` DESC");
					   
					   $p = 0;				   
					   foreach($exchanges as $exchange) {
							$checkOrder = $Changer_API->checkExchange($exchange->exchange_id);
							
							if ($p % 2 == 0) { 
								$b = "background-color: #FFF;"; 
							} else { 
								$b = "background-color: #DDDDDD;"; 
							}
							
							if($bold_txxs=="") { $bold_txxs = "color: #666666; "; } else { $bold_txxs = ""; }								  
							if($checkOrder['status']=="processing") { $status = '<b style="color: #00FF00;">' . $checkOrder['status'] . '</b>'; } else { if ($checkOrder['status'] == 'new') {$status = '<b style="color: #FF0000;">' . $checkOrder['status'] . '</b>';} else {$status = '<b style="color: #36a97f;">' . $checkOrder['status'] . '</b>';} }
							
							echo '<tr>						   
							   <td align="left" style="'.$bold_txxs.'padding: 7px; ' . $b . ';" nowrap>'.$exchange->id.'</td>
							   <td align="left" style="'.$bold_txxs.'padding: 7px; ' . $b . ';" nowrap>'.$exchange->exchange_id.'</td>						   
							   <td align="right" style="'.$bold_txxs.'padding: 7px; ' . $b . ';" nowrap>'.$exchange->send_amount.' BTC</td>
							   <td align="right" style="'.$bold_txxs.'padding: 7px; ' . $b . ';" nowrap>$'.number_format($exchange->rate, 2).' USD</td>
							   <td align="right" style="'.$bold_txxs.'padding: 7px; ' . $b . ';" nowrap>$'.number_format($exchange->receive_amount, 2).' USD</td>
							   <td align="right" style="'.$bold_txxs.'padding: 7px; ' . $b . '; text-align: left;" nowrap>'.strtoupper($status).'</td>
							   <td align="left" style="'.$bold_txxs.'padding: 7px; ' . $b . ';" nowrap>'.date('n/j/Y h:i a',$exchange->created).'</td>
							</tr>';
							
							$p++;
					   }				   
				   ?>
				</tbody>						
			</table>
	
			<div style="clear:both;width:100%;border-top:1px solid #0a0a0a;margin-top:10px;margin-bottom:10px;"></div>
			
			<div style="color: #555555;">We Accept <a href="https://www.coinbase.com/checkouts/9af1594677125214ee9473a219c16c4f" target="_blank">Bitcoin Donations</a></div>		
			
		</div>				
		<?php
	}	
}
$CaptchaCOIN = new CaptchaCOIN;
?>