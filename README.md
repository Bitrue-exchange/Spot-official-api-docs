# Public Rest API for Bitrue (2021-11-05)
# General API Information
* The base endpoint is: **https://openapi.bitrue.com**
* All endpoints return either a JSON object or array.
* All time and timestamp related fields are in milliseconds.
* All data timestamp:GMT +8
* HTTP `4XX` return codes are used for for malformed requests;
  the issue is on the sender's side.
* HTTP `5XX` return codes are used for internal errors; the issue is on
  Bitrue's side.
  It is important to **NOT** treat this as a failure operation; the execution status is
  **UNKNOWN** and could have been a success.
* Any endpoint can return an ERROR; the error payload is as follows:
```json
{
  "code": -1121,
  "msg": "Invalid symbol."
}
```

* Specific error codes and messages defined in another document.
* For `GET` endpoints, parameters must be sent as a `query string`.
* For `POST`, `PUT`, and `DELETE` endpoints, the parameters may be sent as a
  `query string` or in the `request body` with content type
  `application/x-www-form-urlencoded`. You may mix parameters between both the
  `query string` and `request body` if you wish to do so.
* Parameters may be sent in any order.
* If a parameter sent in both the `query string` and `request body`, the
  `query string` parameter will be used.

# LIMITS 
* The `/api/v1/exchangeInfo` `rateLimits` array contains objects related to the exchange's `REQUEST_WEIGHT` and `ORDER` rate limits.
* A 429 will be returned when either rate limit is violated.
* Each route has a `weight` which determines for the number of requests each endpoint counts for. Heavier endpoints and endpoints that do operations on multiple symbols will have a heavier `weight`.
* When a 429 is recieved, it's your obligation as an API to back off and not spam the API.
* **Repeatedly violating rate limits and/or failing to back off after receiving 429s will result in an automated IP ban .**
* IP bans are tracked and **scale in duration** for repeat offenders, **from 2 minutes to 3 days**.

# Endpoint security type
* Each endpoint has a security type that determines the how you will
  interact with it.
* API-keys are passed into the Rest API via the `X-MBX-APIKEY`
  header.
* API-keys and secret-keys **are case sensitive**.

Security Type | Description
------------ | ------------
NONE | Endpoint can be accessed freely.
TRADE | Endpoint requires sending a valid API-Key and signature.
MARKET_DATA | Endpoint requires sending a valid API-Key.

* `TRADE` endpoints are `SIGNED` endpoints.

# SIGNED (TRADE and USER_DATA) Endpoint security
* `SIGNED` endpoints require an additional parameter, `signature`, to be
  sent in the  `query string` or `request body`.
* Endpoints use `HMAC SHA256` signatures. The `HMAC SHA256 signature` is a keyed `HMAC SHA256` operation.
  Use your `secretKey` as the key and `totalParams` as the value for the HMAC operation.
* The `signature` is **not case sensitive**.
* `totalParams` is defined as the `query string` concatenated with the
  `request body`.

## Timing security
* A `SIGNED` endpoint also requires a parameter, `timestamp`, to be sent which
  should be the millisecond timestamp of when the request was created and sent.
* An additional parameter, `recvWindow`, may be sent to specify the number of
  milliseconds after `timestamp` the request is valid for. If `recvWindow`
  is not sent, **it defaults to 5000**.
* The logic is as follows:
  ```javascript
  if (timestamp < (serverTime + 1000) && (serverTime - timestamp) <= recvWindow) {
    // process request
  } else {
    // reject request
  }
  ```

**Serious trading is about timing.** Networks can be unstable and unreliable,
which can lead to requests taking varying amounts of time to reach the
servers. With `recvWindow`, you can specify that the request must be
processed within a certain number of milliseconds or be rejected by the
server.


**It recommended to use a small recvWindow of 5000 or less!**


## SIGNED Endpoint Examples for POST /api/v1/order
Here is a step-by-step example of how to send a vaild signed payload from the
Linux command line using `echo`, `openssl`, and `curl`.

Key | Value
------------ | ------------
apiKey | vmPUZE6mv9SD5VNHk4HlWFsOr6aKE2zvsw0MuIgwCIPy6utIco14y7Ju91duEh8A
secretKey | NhqPtmdSJYdKjVHjA7PZj4Mge3R5YNiP1e3UZjInClVN65XAbvqqM6A7H5fATj0j


Parameter | Value
------------ | ------------
symbol | LTCBTC
side | BUY
type | LIMIT
timeInForce | GTC
quantity | 1
price | 0.1
recvWindow | 5000
timestamp | 1499827319559


### Example 1: As a query string
* **queryString:** symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTC&quantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559
* **HMAC SHA256 signature:**

    ```
    [linux]$ echo -n "symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTC&quantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559" | openssl dgst -sha256 -hmac "NhqPtmdSJYdKjVHjA7PZj4Mge3R5YNiP1e3UZjInClVN65XAbvqqM6A7H5fATj0j"
    (stdin)= c8db56825ae71d6d79447849e617115f4a920fa2acdcab2b053c4b2838bd6b71
    ```


* **curl command:**

    ```
    (HMAC SHA256)
    [linux]$ curl -H "X-MBX-APIKEY: vmPUZE6mv9SD5VNHk4HlWFsOr6aKE2zvsw0MuIgwCIPy6utIco14y7Ju91duEh8A" -X POST 'https://www.bitrue.com/api/v1/order?symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTC&quantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559&signature=c8db56825ae71d6d79447849e617115f4a920fa2acdcab2b053c4b2838bd6b71'
    ```

### Example 2: As a request body
* **requestBody:** symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTC&quantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559
* **HMAC SHA256 signature:**

    ```
    [linux]$ echo -n "symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTC&quantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559" | openssl dgst -sha256 -hmac "NhqPtmdSJYdKjVHjA7PZj4Mge3R5YNiP1e3UZjInClVN65XAbvqqM6A7H5fATj0j"
    (stdin)= c8db56825ae71d6d79447849e617115f4a920fa2acdcab2b053c4b2838bd6b71
    ```


* **curl command:**

    ```
    (HMAC SHA256)
    [linux]$ curl -H "X-MBX-APIKEY: vmPUZE6mv9SD5VNHk4HlWFsOr6aKE2zvsw0MuIgwCIPy6utIco14y7Ju91duEh8A" -X POST 'https://www.bitrue.com/api/v1/order' -d 'symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTC&quantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559&signature=c8db56825ae71d6d79447849e617115f4a920fa2acdcab2b053c4b2838bd6b71'
    ```

### Example 3: Mixed query string and request body
* **queryString:** symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTC
* **requestBody:** quantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559
* **HMAC SHA256 signature:**

    ```
    [linux]$ echo -n "symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTCquantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559" | openssl dgst -sha256 -hmac "NhqPtmdSJYdKjVHjA7PZj4Mge3R5YNiP1e3UZjInClVN65XAbvqqM6A7H5fATj0j"
    (stdin)= 0fd168b8ddb4876a0358a8d14d0c9f3da0e9b20c5d52b2a00fcf7d1c602f9a77
    ```


* **curl command:**

    ```
    (HMAC SHA256)
    [linux]$ curl -H "X-MBX-APIKEY: vmPUZE6mv9SD5VNHk4HlWFsOr6aKE2zvsw0MuIgwCIPy6utIco14y7Ju91duEh8A" -X POST 'https://www.bitrue.com/api/v1/order?symbol=LTCBTC&side=BUY&type=LIMIT&timeInForce=GTC' -d 'quantity=1&price=0.1&recvWindow=5000&timestamp=1499827319559&signature=0fd168b8ddb4876a0358a8d14d0c9f3da0e9b20c5d52b2a00fcf7d1c602f9a77'
    ```

Note that the signature is different in example 3.
There is no & between "GTC" and "quantity=1".

# Public API Endpoints
## Terminology
* `base asset` refers to the asset that is the `quantity` of a symbol.
* `quote asset` refers to the asset that is the `price` of a symbol.


## ENUM definitions
**Symbol status:**

* TRADING
* HALT


**Order status:**

* NEW
* PARTIALLY_FILLED
* FILLED
* CANCELED
* PENDING_CANCEL (currently unused)
* REJECTED
* EXPIRED

**Order types:**

* LIMIT
* MARKET

**Order side:**

* BUY
* SELL

**Time in force:**

* GTT

**Kline/Candlestick chart intervals:**

m -> minutes; h -> hours; d -> days; w -> weeks; M -> months

* 1m
* 5m
* 15m
* 30m
* 1h
* 1d
* 1w
* 1M

**Rate limiters (rateLimitType)**

* REQUESTS_WEIGHT
* ORDERS

**Rate limit intervals**

* SECOND
* MINUTE
* DAY

## General endpoints
### Test connectivity
```
GET /api/v1/ping
```
Test connectivity to the Rest API.

**Weight:**
1

**Parameters:**
NONE

**Response:**
```json
{}
```

### Check server time
```
GET /api/v1/time
```
Test connectivity to the Rest API and get the current server time.

**Weight:**
1

**Parameters:**
NONE

**Response:**
```json
{
  "serverTime": 1499827319559
}
```

### Exchange information （Some fields not support. only reserved）
```
GET /api/v1/exchangeInfo
```
Current exchange trading rules and symbol information

**Weight:**
1

**Parameters:**
NONE

**Response:**
```json
{
  "timezone": "UTC",
  "serverTime": 1508631584636,
  "rateLimits": [{
      "rateLimitType": "REQUESTS_WEIGHT",
      "interval": "MINUTE",
      "limit": 1200
    },
    {
      "rateLimitType": "ORDERS",
      "interval": "SECOND",
      "limit": 10
    },
    {
      "rateLimitType": "ORDERS",
      "interval": "DAY",
      "limit": 100000
    }
  ],
  "exchangeFilters": [],
  "symbols": [{
    "symbol": "ETHBTC",
    "status": "TRADING",
    "baseAsset": "ETH",
    "baseAssetPrecision": 8,
    "quoteAsset": "BTC",
    "quotePrecision": 8,
    "orderTypes": ["LIMIT", "MARKET"],
    "icebergAllowed": false,
    "filters": [{
      "filterType": "PRICE_FILTER",
      "minPrice": "0.00000100",
      "maxPrice": "100000.00000000",
      "tickSize": "0.00000100"
    }, {
      "filterType": "LOT_SIZE",
      "minQty": "0.00100000",
      "maxQty": "100000.00000000",
      "stepSize": "0.00100000"
    }, {
      "filterType": "MIN_NOTIONAL",
      "minNotional": "0.00100000"
    }]
  }],
  "coins": [{
    "coin":"btr",
    "coinFulName":"Bitrue Coin",
    "enableWithdraw":true,
    "enableDeposit":true,
    "chains":["ERC20","BEP20"],
    "withdrawFee":"161.0",
    "minWithdraw":"1961.067474",
    "maxWithdraw":"88888888"
  }]
}
```

## Market Data endpoints
### Order book
```
GET /api/v1/depth
```

**Weight:**
Adjusted based on the limit:


Limit | Weight
------------ | ------------
5, 10, 20, 50, 100 | 1
500 | 5
1000 | 10

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
limit | INT | NO | Default 100; max 1000. Valid limits:[5, 10, 20, 50, 100, 500, 1000]

**Caution:** setting limit=0 can return a lot of data.

**Response:**
```json
{
  "lastUpdateId": 1027024,
  "bids": [
    [
      "4.00000000",     // PRICE
      "431.00000000",   // QTY
      []                // Ignore.
    ]
  ],
  "asks": [
    [
      "4.00000200",
      "12.00000000",
      []
    ]
  ]
}
```

### Recent trades list
```
GET /api/v1/trades
```
Get recent trades (up to last 1000).

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
limit | INT | NO | Default 100; max 1000.

**Response:**
```json
[
  {
    "id": 28457,
    "price": "4.00000100",
    "qty": "12.00000000",
    "time": 1499865549590,
    "isBuyerMaker": true, 
    "isBestMatch": true 
  }
]
```

### Old trade lookup (MARKET_DATA)
```
GET /api/v1/historicalTrades
```
Get older trades.

**Weight:**
5

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
limit | INT | NO | Default 100; max 1000.
fromId | LONG | NO | TradeId to fetch from. Default gets most recent trades.

**Response:**
```json
[
  {
    "id": 28457,
    "price": "4.00000100",
    "qty": "12.00000000",
    "time": 1499865549590,
    "isBuyerMaker": true,
    "isBestMatch": true  
  }
]
```

### Compressed/Aggregate trades list
```
GET /api/v1/aggTrades
```
Get compressed, aggregate trades. Trades that fill at the time, from the same
order, with the same price will have the quantity aggregated.

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
fromId | LONG | NO | ID to get aggregate trades from INCLUSIVE.
startTime | LONG | NO | Timestamp in ms to get aggregate trades from INCLUSIVE.
endTime | LONG | NO | Timestamp in ms to get aggregate trades until EXCLUSIVE.
limit | INT | NO | Default 100; max 1000.

* If both startTime and endTime are sent, time between startTime and endTime must be less than 1 hour.
* If fromId, startTime, and endTime are not sent, the most recent aggregate trades will be returned.

**Response:**
```json
[
  {
    "a": 26129,         // Aggregate tradeId
    "p": "0.01633102",  // Price
    "q": "4.70443515",  // Quantity
    "f": 27781,         // First tradeId
    "l": 27781,         // Last tradeId
    "T": 1498793709153, // Timestamp
    "m": true,          // Was the buyer the maker?
    "M": true           // Was the trade the best price match?
  }
]
```

### 24hr ticker price change statistics
```
GET /api/v1/ticker/24hr
```
24 hour price change statistics. **Careful** when accessing this with no symbol.

**Weight:**
1 for a single symbol; **40** when the symbol parameter is omitted

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | NO |

24 hour price change statistics. **Careful** when accessing this with no symbol.
**Weight:**
1 for a single symbol; **40** when the symbol parameter is omitted

**Response:**
```json
{
  "symbol": "BNBBTC",
  "priceChange": "-94.99999800",
  "priceChangePercent": "-95.960",
  "weightedAvgPrice": "0.29628482",
  "prevClosePrice": "0.10002000",
  "lastPrice": "4.00000200",
  "lastQty": "200.00000000",
  "bidPrice": "4.00000000",
  "askPrice": "4.00000200",
  "openPrice": "99.00000000",
  "highPrice": "100.00000000",
  "lowPrice": "0.10000000",
  "volume": "8913.30000000",
  "quoteVolume": "15.30000000",
  "openTime": 1499783499040,
  "closeTime": 1499869899040,
  "firstId": 28385,   // First tradeId
  "lastId": 28460,    // Last tradeId
  "count": 76         // Trade count
}
```
OR
```json
[
  {
    "symbol": "BNBBTC",
    "priceChange": "-94.99999800",
    "priceChangePercent": "-95.960",
    "weightedAvgPrice": "0.29628482",
    "prevClosePrice": "0.10002000",
    "lastPrice": "4.00000200",
    "lastQty": "200.00000000",
    "bidPrice": "4.00000000",
    "askPrice": "4.00000200",
    "openPrice": "99.00000000",
    "highPrice": "100.00000000",
    "lowPrice": "0.10000000",
    "volume": "8913.30000000",
    "quoteVolume": "15.30000000",
    "openTime": 1499783499040,
    "closeTime": 1499869899040,
    "firstId": 28385,   // First tradeId
    "lastId": 28460,    // Last tradeId
    "count": 76         // Trade count
  }
]
```

### Symbol price ticker
```
GET /api/v1/ticker/price
```
Latest price for a symbol or symbols.

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |


**Response:**
```json
{
  "symbol": "LTCBTC",
  "price": "4.00000200"
}
```



### Symbol order book ticker
```
GET /api/v1/ticker/bookTicker
```
Best price/qty on the order book for a symbol or symbols.

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |

**Response:**
```json
{
  "symbol": "LTCBTC",
  "bidPrice": "4.00000000",
  "bidQty": "431.00000000",
  "askPrice": "4.00000200",
  "askQty": "9.00000000"
}
```

## Account endpoints
### New order  (TRADE)
```
POST /api/v1/order  (HMAC SHA256)
```
Send in a new order.

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
side | ENUM | YES |
type | ENUM | YES |
timeInForce | ENUM | NO |
quantity | DECIMAL | YES |
price | DECIMAL | NO |
newClientOrderId | STRING | NO | A unique id for the order. Automatically generated if not sent. 
stopPrice | DECIMAL | NO |   
icebergQty | DECIMAL | NO | 
recvWindow | LONG | NO |
timestamp | LONG | YES |

Additional mandatory parameters based on `type`:

Type | Additional mandatory parameters
------------ | ------------
`LIMIT` |  `quantity`, `price`
`MARKET` | `quantity`

**Response :**
```json
{
  "symbol": "BTCUSDT",
  "orderId": 28,
  "clientOrderId": "6gCrw2kRUAF9CvJDGP16IP",
  "transactTime": 1507725176595
}
```

### Query order (USER_DATA)
```
GET /api/v1/order (HMAC SHA256)
```
Check an order's status.

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
orderId | LONG | YES |
origClientOrderId | STRING | NO | 
recvWindow | LONG | NO |
timestamp | LONG | YES |

**Response:**
```json
{
  "symbol": "LTCBTC",
  "orderId": 1,
  "clientOrderId": "myOrder1",  
  "price": "0.1",
  "origQty": "1.0", 
  "executedQty": "0.0", 
  "cummulativeQuoteQty": "0.0", 
  "status": "NEW",
  "timeInForce": "GTC", 
  "type": "LIMIT",
  "side": "BUY",
  "stopPrice": "0.0",  
  "icebergQty": "0.0", 
  "time": 1499827319559,
  "updateTime": 1499827319559,
  "isWorking": true  
}
```

### Cancel order (TRADE)
```
DELETE /api/v1/order  (HMAC SHA256)
```
Cancel an active order.

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
orderId | LONG | NO |
origClientOrderId | STRING | NO |  
newClientOrderId | STRING | NO |  
recvWindow | LONG | NO |
timestamp | LONG | YES |


**Response:**
```json
{
  "symbol": "LTCBTC",
  "origClientOrderId": "myOrder1",  
  "orderId": 1,
  "clientOrderId": "cancelMyOrder1"  
}
```

### Current open orders (USER_DATA)
```
GET /api/v1/openOrders  (HMAC SHA256)
```

**Weight:**
1 for a single symbol;

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
recvWindow | LONG | NO |
timestamp | LONG | YES |


**Response:**
```json
[
  {
    "symbol": "LTCBTC",
    "orderId": 1,
    "clientOrderId": "myOrder1",
    "price": "0.1",
    "origQty": "1.0",  
    "executedQty": "0.0",  
    "cummulativeQuoteQty": "0.0",  
    "status": "NEW",
    "timeInForce": "GTC",  
    "type": "LIMIT",
    "side": "BUY",
    "stopPrice": "0.0", 
    "icebergQty": "0.0",  
    "time": 1499827319559,
    "updateTime": 1499827319559,
    "isWorking": true  
  }
]
```

### All orders (USER_DATA)
```
GET /api/v1/allOrders (HMAC SHA256)
```
Get all account orders; active, canceled, or filled.

**Weight:**
5 with symbol

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
orderId | LONG | NO |
startTime | LONG | NO |
endTime | LONG | NO |
limit | INT | NO | Default 100; max 1000.
recvWindow | LONG | NO |
timestamp | LONG | YES |

**Notes:**
* If `orderId` is set, it will get orders >= that `orderId`. Otherwise most recent orders are returned.

**Response:**
```json
[
  {
    "symbol": "LTCBTC",
    "orderId": 1,
    "clientOrderId": "myOrder1",
    "price": "0.1",
    "origQty": "1.0",
    "executedQty": "0.0",
    "cummulativeQuoteQty": "0.0",
    "status": "NEW",
    "timeInForce": "GTC",
    "type": "LIMIT",
    "side": "BUY",
    "stopPrice": "0.0",
    "icebergQty": "0.0",
    "time": 1499827319559,
    "updateTime": 1499827319559,
    "isWorking": true
  }
]
```

### Account information (USER_DATA)
```
GET /api/v1/account (HMAC SHA256)
```
Get current account information.

**Weight:**
5

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
recvWindow | LONG | NO |
timestamp | LONG | YES |

**Response:**
```json
{
  "makerCommission": 15,   
  "takerCommission": 15,  
  "buyerCommission": 0,   
  "sellerCommission": 0,   
  "canTrade": true,   
  "canWithdraw": true,  
  "canDeposit": true,  
  "updateTime": 123456789,
  "balances": [
    {
      "asset": "BTC",
      "free": "4723846.89208129",
      "locked": "0.00000000"
    },
    {
      "asset": "LTC",
      "free": "4763368.68006011",
      "locked": "0.00000000"
    }
  ]
}
```

### Account trade list (USER_DATA)
```
GET /api/v1/myTrades  (HMAC SHA256)
```
Get trades for a specific account and symbol.

**Weight:**
5 with symbol;40 when the symbol parameter is omitted

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | NO |
startTime | LONG | NO |
endTime | LONG | NO |
fromId | LONG | NO | TradeId to fetch from. Default gets most recent trades.
limit | INT | NO | Default 100; max 1000.
recvWindow | LONG | NO |
timestamp | LONG | YES |

**Notes:**
* If `fromId` is set, it will get orders >= that `fromId`.
Otherwise most recent orders are returned.

**Response:**
```json
[
  {
    "symbol": "BNBBTC",
    "id": 28457,
    "orderId": 100234,
    "price": "4.00000100",
    "qty": "12.00000000",
    "commission": "10.10000000",
    "commissionAsset": "BNB",
    "time": 1499865549590,
    "isBuyer": true,
    "isMaker": false,
    "isBestMatch": true    
  }
]
```



### Account trade list (USER_DATA)
```
GET /api/v2/myTrades  (HMAC SHA256)
```
Get trades for a specific account and symbol.

**Weight:**
5 with symbol;40 when the symbol parameter is omitted

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |
startTime | LONG | NO |
endTime | LONG | NO |
fromId | LONG | NO | TradeId to fetch from. Default gets most recent trades.
limit | INT | NO | Default 100; max 1000.
recvWindow | LONG | NO |
timestamp | LONG | YES |

**Notes:**
* If `fromId` is set, it will get orders >= that `fromId`.
Otherwise most recent orders are returned.

**Response:**
```json
[
  {
    "symbol": "BNBBTC",
    "id": 28457,
    "orderId": 100234,
    "price": "4.00000100",
    "qty": "12.00000000",
    "commission": "10.10000000",
    "commissionAsset": "BNB",
    "time": 1499865549590,
    "isBuyer": true,
    "isMaker": false,
    "isBestMatch": true    
  }
]
```
### ETF net value  (MARKET_DATA)

```
GET /api/v1/etf/net-value/{symbol}  (HMAC SHA256)
```

Get etf net value for a specific symbol.

**Weight:**
1 

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
symbol | STRING | YES |

**Notes:**

path variable symbol should set symbol ,for example 

/api/v1/etf/net-value/xrp3lusdt

**Response:**

```json
{
"id": 5916134,
"symbol": "xrp3lusdt", //ETF symbol name 
"futruesPrice": 1.1786,  // contract price
"netValue": 1.079792003418094,   // net value 
"beginNetValue": 1.0075782872361934, // net value on the beginning
"beginFutruesPrice": 1.1511,  // contract price on the beginning
"seqId": 182101153490862080,  // sequence id 
"beginTs": 1629144393980, // timestamp on the beginning
"ts": 1629147837595 // timestamp of this data 
}
```



## Deposit & Withdraw (after 2021-10-12)

### Withdraw commit  (WITHDRAW_DATA)

```
POST /api/v1/withdraw/commit
```

Commit one withdraw request.

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
coin | STRING | YES | Coin name without chain name.
amount | NUMBER | YES | How much coins to withdraw.
addressTo | STRING | YES | Which address to withdraw.
chainName | STRING | YES | Which chain to withdraw for this coin.
addressMark | STRING | NO | Mark of address.
addrType | STRING | NO | Type of address.
tag | STRING | NO | Tag for address.

**Notes:**

This method needs the API withdraw privilege and you MUST set limit IP for this API Key and you MUST set withdraw address white list before.

**Response:**

```json
{
    "code": 200,
    "msg": "succ",
    "data": {
        "msg": null,
        "amount": 1000,
        "fee": 1,
        "ctime": null,
        "coin": "usdt_erc20",
        "addressTo": "0x2edfae3878d7b6db70ce4abed177ab2636f60c83"
    }
}
```

ErrorCode | Description
------------ | ------------ 
2 | Parameter error
6 | Withdraw amount too less
19 | Insufficient balance
32 | User auth error
110049 | Withdraw locked
110050 | Withdraw locked
110051 | Withdraw locked
110054 | Withdraw locked
110055 | Withdraw locked
110056 | Withdraw locked
110057 | Withdraw locked
966000 | Ip limit
966001 | No privileges
999503 | Withdraw fee error
999504 | Withdraw limit
999505 | Withdraw limit
999508 | Withdraw locked
999509 | Not deposit found
999512 | Withdraw tag error
999513 | Withdraw address error
999901 | Coin chain error
999902 | Account balance error

### Withdraw history  (WITHDRAW_DATA)

```
GET /api/v1/withdraw/history
```

Query withdraw history

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
coin | STRING | YES | Coin name without chain name.
status | NUMBER | NO | 0: init 5: finished 6: canceled. Default 0.
offset | NUMBER | NO | Which offset to start. Default 0.
limit | NUMBER | NO | Limit data to query. Default 10. Max 1000.
startTime | NUMBER | NO | Start time to query. Timestamp in ms. 
endTime | NUMBER | NO | End time to query. Timestamp in ms.

**Notes:**

The other status means your withdraw request is in a flow.

**Response:**

```json
{
    "code": 200,
    "msg": "succ",
    "data": [
                {
                    "id": 183745,
                    "symbol": "usdt_erc20",
                    "amount": "8.4000000000000000",
                    "fee": "1.6000000000000000",
                    "payAmount": "0.0000000000000000",
                    "createdAt": 1595336441000,
                    "updatedAt": 1595336576000,
                    "addressFrom": "",
                    "addressTo": "0x2edfae3878d7b6db70ce4abed177ab2636f60c83",
                    "txid": "",
                    "confirmations": 0,
                    "status": 6,
                    "tagType": null
                }
            ]
}
```

### Deposit history  (WITHDRAW_DATA)

```
GET /api/v1/deposit/history
```

Query deposit history

**Weight:**
1

**Parameters:**

Name | Type | Mandatory | Description
------------ | ------------ | ------------ | ------------
coin | STRING | YES | Coin name without chain name.
status | NUMBER | NO | 0: init 1: finished. Default 0.
offset | NUMBER | NO | Which offset to start. Default 0.
limit | NUMBER | NO | Limit data to query. Default 10. Max 1000.
startTime | NUMBER | NO | Start time to query. Timestamp in ms. 
endTime | NUMBER | NO | End time to query. Timestamp in ms.


**Response:**

```json
{
    "code": 200,
    "msg": "succ",
    "data": [
                {
                    "symbol": "XRP",
                    "amount": "261.3361000000000000",
                    "fee": "0.0E-15",
                    "createdAt": 1548816979000,
                    "updatedAt": 1548816999000,
                    "addressFrom": "",
                    "addressTo": "raLPjTYeGezfdb6crXZzcC8RkLBEwbBHJ5_18113641",
                    "txid": "86D6EB68A7A28938BCE06BD348F8C07DEF500C5F7FE92069EF8C0551CE0F2C7D",
                    "confirmations": 8,
                    "status": 1,
                    "tagType": "Tag"
                },
                {
                    "symbol": "XRP",
                    "amount": "20.0000000000000000",
                    "fee": "0.0E-15",
                    "createdAt": 1544669393000,
                    "updatedAt": 1544669413000,
                    "addressFrom": "",
                    "addressTo": "raLPjTYeGezfdb6crXZzcC8RkLBEwbBHJ5_18113641",
                    "txid": "515B23E1F9864D3AF7F5B4C4FCBED784BAE861854FAB95F4031922B6AAEFC7AC",
                    "confirmations": 7,
                    "status": 1,
                    "tagType": "Tag"
                }
            ]
}
```

# # User Data Streams （after 2021-11-05）

- The base API endpoint is: https://open.bitrue.com
- USER_STREAM : Security Type, Endpoint requires sending a valid API-Key.
- API-keys are passed into the API via the X-MBX-APIKEY header.
- API-keys are case sensitive.
- A User Data Stream listenKey is valid for 60 minutes after creation.
- Doing a PUT on a listenKey will extend its validity for 60 minutes.
- Doing a DELETE on a listenKey will close the stream and invalidate the listenKey.
- Doing a POST on an account with an active listenKey will return the currently active listenKey and extend its validity for 60 minutes.
- The base websocket endpoint is: wss://wsapi.bitrue.com
- User Data Streams are accessed at /stream?listenKey=<your listenKey> 
- A single connection to wsapi.bitrue.com is only valid for 24 hours; expect to be disconnected at the 24 hour mark

Error Codes

Errors consist of two parts: an error code and a message. Codes are universal, but messages can vary.

200   SUCCESS

* 200 for success,others are error codes.

503   SERVICE_ERROR

* An unknown error occurred while processing the request.

-1022 INVALID_API_KEY

* You are not authorized to execute this request.

-1102 MANDATORY_PARAM_EMPTY_OR_MALFORMED

* A mandatory parameter was not sent, was empty/null, or malformed.

-1150 INVALID_LISTEN_KEY

* This listenKey does not exist.

## ListenKey

**CREATE A LISTENKEY (USER_STREAM)**

**url**

`POST /poseidon/api/v1/listenKey`

Start a new user data stream. The stream will close after 60 minutes unless a keepalive is sent. If the account has an active listenKey, that listenKey will be returned and its validity will be extended for 60 minutes.

**Response:**

```json
{
  "msg": "succ",
  "code": 200,
  "data":
  {
    "listenKey": "ac3abbc8ac18f7977df42de27ab0c87c1f4ea3919983955d2fb5786468ccdb07"
  }
}
```

**Data Source**: Memory


#### Ping/Keep-alive a ListenKey (USER_STREAM)

**url**

`PUT /poseidon/api/v1/listenKey/{listenKey}`
Keepalive a user data stream to prevent a time out. User data streams will close after 60 minutes. It's recommended to send a ping about every 30 minutes.

**Response:**

```json
{
  "msg": "succ",
  "code": 200
}
```

**Data Source**: Memory


#### Close a ListenKey (USER_STREAM)

**url:**

DELETE /poseidon/api/v1/listenKey/{listenKey}

Close out a user data stream.

**Response:**

```json
{
  "msg": "succ",
  "code": 200
}
```

**Data Source**: Memory


## keep-alive 

you should send pong message within 10 minutes
**pong：**

``` 
{"event":"pong","ts":"1635221621062"}
```


## Order Update

Orders are updated with the Order Event 
**subscribe：**

``` 
{"event":"sub","params":{"channel":"user_order_update"}}
```

**response：**

sub success :

``` 
{"channel":"user_order_update","event_rep":"subed","status":"ok","ts":1623381851178}
```

order event :

``` 
{
  "e": "executionReport",        // Event type
  "I": "209818131719847936",     // Event ID
  "E": 1499405658658,            // Event time
  "u": 123456,                   // UID
  "s": "ETHBTC",                 // Symbol
  "c": "mUvoqJxFIILMdfAW5iGSOW", // Client order ID
  "S": "BUY",                    // Side
  "o": "LIMIT",                  // Order type
  "q": "1.00000000",             // Order quantity
  "p": "0.10264410",             // Order price
  "x": "NEW",                    // order event
  "X": "NEW",                    // Current order status
  "i": 4293153,                  // Order ID
  "l": "0.00000000",             // Last executed quantity
  "L": "0.00000000",             // Last executed price
  "n": "0",                      // Commission amount
  "N": null,                     // Commission asset
  "T": 1499405658657,            // Trade time
  "t": -1,                       // Trade ID
  "O": 1499405658657,            // Order creation time
  "z": "0.00000000",              // Cumulative filled quantity
  "Y": "0.00000000",             // Cumulative transacted amount (i.e. Price * Qty)
}
```

**unsubscribe：**

``` 
{"event":"unsub","params":{"channel":"user_order_update"}}
```

**response：**

``` 
{"channel":"user_order_update","status":"ok","ts":1623381851178}
```

**example:**

subscribe

``` 
{"event":"sub","params":{"channel":"user_order_update"}}
```

unsubscribe

``` 
{"event":"unsub","params":{"channel":"user_order_update"}}
```

## balance update

balance are updated with the Balance Event 
**subscribe：**

``` 
{"event":"sub","params":{"channel":"user_balance_update"}}
```

**response：**

``` 
{
    "e":"BALANCE",  #event name 
    "x":"OutboundAccountPositionOrderEvent", #event type  
    "E":1635515839203,  #event time 
    "I":208810488108744704,  #event id 
    "i":1635515839203,  # ignore 
    "B":[
        {
            "a":"btr",        # assert name 
            "F":"9999999.9658620755200000",  # balance 
            "T":1635515839000,  # balance update time 
            "f":"2.8125000000000000",  # balance  Delta
            "L":"0.0000000000000000", # lock balance  
            "l":"-2.8125000000000000", # lock balance  Delta 
            "t":1635515839000 #  lock balance  update time 
        },
        {
            "a":"usdt",
            "F":"10000008.8000000000000000",
            "T":1635515839000,
            "f":"10.2600000000000000",
            "L":"0.0000000000000000",
            "l":"-10.2600000000000000",
            "t":1635515839000
        }
    ],
    "u":1090862
}
```

**unsubscribe：**

``` 
{"event":"unsub","params":{"channel":"user_balance_update"}}
```

**response：**

``` 
{"channel":"user_balance_update","status":"ok","ts":1623381851178}
```

##Terminology
These terms will be used throughout the documentation, so it is recommended especially for new users to read to help their understanding of the API.

base asset refers to the asset that is the quantity of a symbol. For the symbol BTCUSDT, BTC would be the base asset.
quote asset refers to the asset that is the price of a symbol. For the symbol BTCUSDT, USDT would be the quote asset.

ENUM definitions

`SPOT`
**balance event :**

Event | 	Description

OutboundAccountPositionEvent  |  ignore 

OutboundAccountPositionTradeEvent | the order has been filled 

OutboundAccountPositionOrderEvent	 | The order place or cancel 

OutboundAccountPositionTransferEvent |	 ignore 

OutboundAccountPositionBonusEvent |  ignore 


**order event :**

Event | 	Description

1	 | The order has been created by the user. 

2    | 	The order has been canceled by the user.

3	 |  The order has been filled by the engine .

4   |	The order has been canceled by the engine .


**order status :**

status | 	Description

0	 | The order has not been accepted by the engine.

1	 | The order has been accepted by the engine.

2    | 	The order has been completed. 

3	 | A part of the order has been filled.

4   |	The order has been canceled by the user.