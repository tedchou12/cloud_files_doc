<?php
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';
require 'delta.php';

// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$data = json_decode($message, true);
	if ($data['type'] == 'save') {
		file_put_contents('data.html', $data['data']);
		$file = fopen('diff.txt', 'w');
		fwrite($file, '');
		fclose($file);
	} else {
		$file = fopen('diff.txt', 'a');
		fwrite($file, $message."\r\n");
		fclose($file);

		//Send the message to everyone but the person who said it
		if ( sizeof($Server->wsClients) != 1 ) {
			foreach ( $Server->wsClients as $id => $client ) {
				if ( $id != $clientID ) {
					$Server->wsSend($id, $message);
				}
			}
		}
	}
}

// when a client connects
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has connected." );

	// send a join notice to everyone but the person who joined
	foreach ( $Server->wsClients as $id => $client ) {
		if ( $id != $clientID ) {
			$Server->wsSend($id, "[CLIENT:JOINED] $clientID $ip");
		} else {
			// send a list of active clients to a new one
			$clients = "";
			foreach ( $Server->wsClients as $id2 => $client2 ) {
				if ( $id2 != $clientID ) {
					$clients .= "$id2," . long2ip($client2[6]) . ";";
				}
			}
			$Server->wsSend($clientID, "[CLIENTS:LIST] $clients");
			$json = array();
			$json['type'] = 'load';
			$json['data'] = file_get_contents('data.html');
			$Server->wsSend($clientID, json_encode($json));
			$data = file('diff.txt', FILE_IGNORE_NEW_LINES);
			$json = array('type' => 'load_diff', 'data' => array('ops' => array()));
			if (count($data)) {
				foreach ($data as $row) {
					$j_row = json_decode($row, true);
					if ($j_row['type'] == 'text-change') {
						$Server->wsSend($clientID, json_encode($j_row));
					}
				}
			}
		}
	}
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has disconnected." );

	//Send a user left notice to everyone in the room
	if (count($Server->wsClients) > 1) {
		foreach ( $Server->wsClients as $id => $client ) {
			$Server->wsSend($id, "[CLIENT:LEFT] $clientID $ip");
		}
	} else {
		// $content = file_get_contents('data.html');
		// $data = file('diff.txt', FILE_IGNORE_NEW_LINES);
		// $delta = new delta($content);
		// foreach ($data as $row) {
		// 	$row = json_decode($row, true);
		// 	if ($row['type'] == 'text-change') {
		// 		$delta->diff($row['data']);
		// 	}
		// }
		// echo $delta->render();
		//
		// file_put_contents('data.html', $delta->render());
		//
		// $file = fopen('diff.txt', 'w');
		// fwrite($file, '');
		// fclose($file);
	}
}

// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer('192.168.1.22', 9300);

?>
