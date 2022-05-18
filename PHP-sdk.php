<?php 

class Bitrue {
	public $btc_value = 0.00;
 	protected $base = "https://www.bitrue.com/api/", $api_key, $api_secret;
	public function __construct($api_key, $api_secret) {
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
		$this->header= '';
	}
	public function ping() {
		return $this->request("v1/ping");
	}
	public function time() {
		return $this->request("v1/time");
	}
	public function exchangeInfo() {
		return $this->request("v1/exchangeInfo");
	}
	public function buy_test($symbol, $quantity, $price, $type = "LIMIT", $flags = []) {
		return $this->order_test("BUY", $symbol, $quantity, $price, $type, $flags);
	}
	public function sell_test($symbol, $quantity, $price, $type = "LIMIT", $flags = []) {
		return $this->order_test("SELL", $symbol, $quantity, $price, $type, $flags);
	}
	public function buy($symbol, $quantity, $price, $type = "LIMIT", $flags = []) {
		return $this->order("BUY", $symbol, $quantity, $price, $type, $flags);
	}
	public function sell($symbol, $quantity, $price, $type = "LIMIT", $flags = []) {
		return $this->order("SELL", $symbol, $quantity, $price, $type, $flags);
	}
	public function cancel($symbol, $orderid) {
		return $this->signedRequest("v1/order", ["symbol"=>$symbol, "orderId"=>$orderid], "DELETE");
	}
	public function orderStatus($symbol, $orderid) {
		if(!$orderid){
			return $this->signedRequest("v1/order",["symbol"=>$symbol]);
		}
		return $this->signedRequest("v1/order",["symbol"=>$symbol, "orderId"=>$orderid]);
	}
	public function openOrders($symbol) {
		return $this->signedRequest("v1/openOrders", ["symbol"=>$symbol]);
	}
	public function currentOrder($symbol, $limit = 500) {
		return $this->signedRequest("v1/order",["symbol"=>$symbol, "limit"=>$limit]);
	}
	public function orders($symbol, $limit = 500) {
		return $this->signedRequest("v1/allOrders",["symbol"=>$symbol]);
	}
	public function trades($symbol, $limit = 500) {
		return $this->request("v1/trades",["symbol"=>$symbol, "limit"=>$limit]);
	}
	public function historyTrades($symbol, $limit = 500, $from_id=0) {
		if($from_id) {
			return $this->request("v1/historicalTrades",["symbol"=>$symbol, "limit"=>$limit, "formId"=>$from_id]);
		}
		return $this->request("v1/historicalTrades",["symbol"=>$symbol, "limit"=>$limit]);
	}
	public function aggTrades($symbol) {
		return $this->request("v1/aggTrades", ["symbol"=>$symbol]);
	}
	//1m,3m,5m,15m,30m,1h,2h,4h,6h,8h,12h,1d,3d,1w,1M
	public function candlesticks($symbol, $interval = "5m") {
		return $this->request("v1/klines",["symbol"=>$symbol, "interval"=>$interval]);
	}
	public function prevDay($symbol='') {
		return $this->request("v1/ticker/24hr", ["symbol"=>$symbol]);
	}
	public function myTrades($symbol) {
		return $this->signedRequest("v1/myTrades", ["symbol"=>$symbol]);
	}
	public function price($symbol) {
		return $this->request("v1/ticker/price", ["symbol"=>$symbol]);
	}
	public function prices() {
		return $this->priceData($this->request("v1/ticker/allPrices"));
	}
	public function bookTicker($symbol) {
		return $this->request("v1/ticker/bookTicker", ["symbol"=>$symbol]);
	}
	public function bookPrices() {
		return $this->bookPriceData($this->request("v1/ticker/allBookTickers"));
	}
	public function account() {
		return $this->signedRequest("v1/account");
	}
	public function depth($symbol) {
		return $this->request("v1/depth",["symbol"=>$symbol]);
	}
	public function balances($priceData = false) {
		return $this->balanceData($this->signedRequest("v1/account"),$priceData);
	}
	private function request($url, $params = [], $method = "GET") {
		$opt = [
			"http" => [
				"method" => $method,
				"header" => "User-Agent: Mozilla/4.0 (compatible; PHP Bitrue API)\r\n"
			]
		];
		$headers = array('User-Agent: Mozilla/4.0 (compatible; PHP Bitrue API)',
			'X-MBX-APIKEY: ' . $this->api_key,
			'Content-type: application/x-www-form-urlencoded');
		$context = stream_context_create($opt);
		$query = http_build_query($params, '', '&');
		$ret = $this->http_get($this->base.$url.'?'.$query, $headers);
                return $ret;
	}
	private function signedRequest($url, $params = [], $method = "GET") {
		if ( empty($this->api_key) ) die("signedRequest error: API Key not set!");
		if ( empty($this->api_secret) ) die("signedRequest error: API Secret not set!");
		
		$timestamp_t = $this->getServerTime();
		if($timestamp_t < 0) {
			$timestamp_t = number_format(microtime(true)*1000,0,'.','');
		}
		$params['timestamp'] = $timestamp_t;
		$query = http_build_query($params, '', '&');
		$signature = hash_hmac('sha256', $query, $this->api_secret);
		$headers = array("User-Agent: Mozilla/4.0 (compatible; PHP Bitrue API)",
			"X-MBX-APIKEY: {$this->api_key}",
			"Content-type: application/x-www-form-urlencoded");
		$opt = [
			"http" => [
				"method" => $method,
				"ignore_errors" => true,
				"header" => "User-Agent: Mozilla/4.0 (compatible; PHP Bitrue API)\r\nX-MBX-APIKEY: {$this->api_key}\r\nContent-type: application/x-www-form-urlencoded\r\n"
			]
		];
		if ( $method == 'GET' ) {
			// parameters encoded as query string in URL
			$endpoint = "{$this->base}{$url}?{$query}&signature={$signature}";
			$ret = $this->http_get($endpoint, $headers);
		} else if ($method == 'POST'){
			$endpoint = "{$this->base}{$url}";
			$params['signature'] = $signature;
			$ret = $this->http_post($endpoint, $params, $headers);
		} else {
			$endpoint = "{$this->base}{$url}?{$query}&signature={$signature}";
			$ret = $this->http_other($method, $endpoint, $headers);
		}
		return $ret;
	}
	private function order_test($side, $symbol, $quantity, $price, $type = "LIMIT", $flags = []) {
		$opt = [
			"symbol" => $symbol,
			"side" => $side,
			"type" => $type,
			"quantity" => $quantity,
			"recvWindow" => 60000
		];
		if ( $type == "LIMIT" ) {
			$opt["price"] = $price;
			$opt["timeInForce"] = "GTC";
		}
		// allow additional options passed through $flags
		if ( isset($flags['recvWindow']) ) $opt['recvWindow'] = $flags['recvWindow'];
		if ( isset($flags['timeInForce']) ) $opt['timeInForce'] = $flags['timeInForce'];
		if ( isset($flags['stopPrice']) ) $opt['stopPrice'] = $flags['stopPrice'];
		if ( isset($flags['icebergQty']) ) $opt['icebergQty'] = $flags['icebergQty'];
		return $this->signedRequest("v1/order/test", $opt, "POST");
	}
	public function order($side, $symbol, $quantity, $price, $type = "LIMIT", $flags = []) {
		$side = strtoupper($side);
		$type = strtoupper($type);
		if (!in_array($side, ['BUY', 'SELL']) ) die("Unsupport side parameters, please check!");
		if (!in_array($type, ['LIMIT', 'MARKET']) ) die("Unsupport type parameters, please check!");
		$opt = [
			"symbol" => $symbol,
			"side" => $side,
			"type" => $type,
			"quantity" => $quantity,
			"recvWindow" => 60000
		];
		if ( $type == "LIMIT" ) {
			$opt["price"] = $price;
			$opt["timeInForce"] = "GTC";
		}
		// allow additional options passed through $flags
		if ( isset($flags['recvWindow']) ) $opt["recvWindow"] = $flags['recvWindow'];
		if ( isset($flags['timeInForce']) ) $opt["timeInForce"] = $flags['timeInForce'];
		if ( isset($flags['stopPrice']) ) $opt['stopPrice'] = $flags['stopPrice'];
		if ( isset($flags['icebergQty']) ) $opt['icebergQty'] = $flags['icebergQty'];
		return $this->signedRequest("v1/order", $opt, "POST");
	}
	private function balanceData($array, $priceData = false) {
		if ( $priceData ) $btc_value = 0.00;
		$balances = [];
		foreach ( $array['balances'] as $obj ) {
			$asset = $obj['asset'];
			$balances[$asset] = ["available"=>$obj['free'], "onOrder"=>$obj['locked'], "btcValue"=>0.00000000];
			if ( $priceData ) {
				if ( $obj['free'] < 0.00000001 ) continue;
				if ( $asset == 'BTC' ) {
					$balances[$asset]['btcValue'] = $obj['free'];
					$btc_value+= $obj['free'];
					continue;
				}
				$btcValue = number_format($obj['free'] * $priceData[$asset.'BTC'],8,'.','');
				$balances[$asset]['btcValue'] = $btcValue;
				$btc_value+= $btcValue;
			}
		}
		if ( $priceData ) {
			uasort($balances, function($a, $b) { return $a['btcValue'] < $b['btcValue']; });
			$this->btc_value = $btc_value;
		}
		return $balances;
	}
	private function bookPriceData($array) {
		$bookprices = [];
		foreach ( $array as $obj ) {
			$bookprices[$obj['symbol']] = [
				"bid"=>$obj['bidPrice'],
				"bids"=>$obj['bidQty'],
				"ask"=>$obj['askPrice'],
				"asks"=>$obj['askQty']
			];
		}
		return $bookprices;
	}
	private function priceData($array) {
		$prices = [];
		foreach ( $array as $obj ) {
			$prices[$obj['symbol']] = $obj['price'];
		}
		return $prices;
	}
	private function tradesData($trades) {
		$output = [];
		foreach ( $trades as $trade ) {
			$price = $trade['p'];
			$quantity = $trade['q'];
			$timestamp = $trade['T'];
			$maker = $trade['m'] ? 'true' : 'false';
			$output[] = ["price"=>$price, "quantity"=> $quantity, "timestamp"=>$timestamp, "maker"=>$maker];
		}
		return $output;
	}

	private function http_post($url, $data, $headers=[])
	{
	    $data = http_build_query($data);
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_POST, TRUE);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $output = curl_exec($curl);
	    curl_close($curl);
	    return $output;
	}

	private function http_get($url, $headers=[], $data=[])
	{
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $output = curl_exec($curl);
	    curl_close($curl);
	    return $output;
	}

	private function http_other($method, $url, $headers=[])
	{
		$curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $output = curl_exec($curl);
	    curl_close($curl);
	    return $output;
	}

	private function getServerTime()
	{
		$t = $this->time();
		$t_info = json_decode($t, true);
		if(isset($t_info['serverTime'])){
			return $t_info['serverTime'];
		}

		return -1;
	}
}
