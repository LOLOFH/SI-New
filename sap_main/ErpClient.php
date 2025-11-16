<?php
// ErpClient.php

/**
 * Client for the SimpleERPApi service (/rest/api) with Basic Auth.
 *
 * Expected endpoints (CAP service SimpleERPApi):
 *   GET  /rest/api/products
 *   GET  /rest/api/customers
 *   POST /rest/api/createOrder (action createOrder(order : Orders))
 *
 * Example initialization:
 *   $erp = new ErpClient(
 *       'http://localhost:4004/rest/api',
 *       'service-user',
 *       'service-user'
 *   );
 */
class ErpClient
{
    private string $baseUrl;
    private ?string $username;
    private ?string $password;
    private int $maxRetries;
    private int $retryDelayMs;

    /**
     * @param string      $baseUrl      Basis-URL des REST-Services, z.B. 'http://localhost:4004/rest/api'
     * @param string|null $username     Basic-Auth Benutzername (z.B. 'service-user')
     * @param string|null $password     Basic-Auth Passwort (z.B. 'service-user')
    * @param int         $maxRetries   Number of attempts on errors (network / 5xx)
    * @param int         $retryDelayMs Wait time between retries in milliseconds
     */
    public function __construct(
        string $baseUrl,
        ?string $username = null,
        ?string $password = null,
        int $maxRetries = 3,
        int $retryDelayMs = 200
    ) {
        $this->baseUrl      = rtrim($baseUrl, '/');
        $this->username     = $username;
        $this->password     = $password;
        $this->maxRetries   = $maxRetries;
        $this->retryDelayMs = $retryDelayMs;
    }

    /**
     * Central HTTP method with retry (for network errors & 5xx responses).
     *
     * @return array{status:int,body:string}
     * @throws \RuntimeException
     */
    private function request(string $method, string $path, ?array $jsonBody = null): array
    {
        $url = $this->baseUrl . $path;

        $bodyString = null;
        $headers    = [
            'Accept: application/json',
        ];

        if ($jsonBody !== null) {
            $bodyString = json_encode($jsonBody);
            $headers[]  = 'Content-Type: application/json';
        }

        $attempt = 0;
        do {
            $attempt++;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // seconds

            // Basic Auth
            if ($this->username !== null && $this->password !== null) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            }

            if ($bodyString !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
            }

            $responseBody = curl_exec($ch);
            $curlErrNo    = curl_errno($ch);
            $curlErrMsg   = curl_error($ch);
            $statusCode   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            // Network error -> retry
            if ($curlErrNo !== 0) {
                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelayMs * 1000);
                    continue;
                }

                throw new RuntimeException(
                    "ERP request failed after {$attempt} attempts: {$curlErrMsg}"
                );
            }

            // 5xx from server -> retry
            if ($statusCode >= 500 && $statusCode < 600 && $attempt < $this->maxRetries) {
                usleep($this->retryDelayMs * 1000);
                continue;
            }

            // Success or non-retriable error
            return [
                'status' => $statusCode,
                'body'   => $responseBody,
            ];
        } while ($attempt < $this->maxRetries);

        throw new RuntimeException("ERP request failed after {$this->maxRetries} attempts.");
    }

    /* ---------- Products ---------- */

    /**
     * Returns all products from the ERP.
     *
     * Expected structure per product:
     *   [
     *     'productID'     => string,
     *     'name'          => string,
     *     'description'   => string|null,
     *     'price'         => float,
     *     'currency'      => string(3)        // or 'currency_code'
     *     'currency_code' => string(3),
     *     'stock'         => int,
     *     'ID'            => string(UUID)     // internal ERP UUID for createOrder
     *   ]
     *
     * @return array<int,array<string,mixed>>
     * @throws \RuntimeException
     */
    public function getProducts(): array
    {
        $res = $this->request('GET', '/products');
        if ($res['status'] !== 200) {
            throw new RuntimeException("Failed to load products, HTTP " . $res['status']);
        }

        $data = json_decode($res['body'], true);
        return is_array($data) ? $data : [];
    }

    /**
     * Find a product by ProductID (e.g. "P-1001").
     * Uses /products and filters in PHP.
     *
     * @return array<string,mixed>|null
     * @throws \RuntimeException
     */
    public function getProductByProductId(string $productId): ?array
    {
        $products = $this->getProducts();
        foreach ($products as $p) {
            if (isset($p['productID']) && $p['productID'] === $productId) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Returns current stock for a ProductID.
     * Returns null if the product does not exist.
     *
     * @throws \RuntimeException
     */
    public function getStockForProduct(string $productId): ?int
    {
        $product = $this->getProductByProductId($productId);
        if (!$product) {
            return null;
        }
        return isset($product['stock']) ? (int)$product['stock'] : null;
    }

    /* ---------- Customers ---------- */

    /**
     * Returns all customers from the ERP.
     *
     * Structure per customer (SimpleERPApi.Customers):
     *   [
     *     'customerID'  => string(UUID),
     *     'name'        => string,
     *     'email'       => string,
     *     'street'      => string|null,
     *     'houseNumber' => string|null,
     *     'city'        => string|null,
     *     'postalCode'  => string|null,
     *     'country'     => string(3)|null
     *   ]
     *
     * @return array<int,array<string,mixed>>
     * @throws \RuntimeException
     */
    public function getCustomers(): array
    {
        $res = $this->request('GET', '/customers');
        if ($res['status'] !== 200) {
            throw new RuntimeException("Failed to load customers, HTTP " . $res['status']);
        }

        $data = json_decode($res['body'], true);
        return is_array($data) ? $data : [];
    }

    /**
     * Find customer by email.
     * Prerequisite: customers exist in the ERP (including name/address).
     *
     * @return array<string,mixed>|null
     * @throws \RuntimeException
     */
    public function findCustomerByEmail(string $email): ?array
    {
        $customers = $this->getCustomers();
        foreach ($customers as $c) {
            if (isset($c['email']) && strcasecmp($c['email'], $email) === 0) {
                return $c;
            }
        }
        return null;
    }

    /* ---------- Orders ---------- */

    /**
    * Create an order in the ERP (action createOrder(order : Orders)).
     *
    * Order type in the API:
     *   orderID     : Integer;
     *   customer    : UUID;
     *   orderDate   : Date;
     *   orderAmount : Decimal(10,2);
     *   currency    : String(3);
     *   orderStatus : Integer;
     *   items       : many OrderItems;
     *
    * OrderItems:
     *   itemID     : Integer;
     *   product    : UUID;
     *   quantity   : Integer;
     *   itemAmount : Decimal(10,2);
     *   currency   : String(3);
     *
    * @param string $customerId ERP customer UUID (Customers.customerID)
     * @param array  $items      Array von Positionen:
     *                           [
     *                             [
     *                               'product'    => <UUID>,         // Products.ID
     *                               'quantity'   => 2,
     *                               'itemAmount' => 19.98,
     *                               'currency'   => 'EUR'
     *                             ],
     *                             ...
     *                           ]
     * @param string $currency   Bestellwährung, z.B. "EUR"
     *
    * @return array{status:int,body:string}
    *         status: HTTP status code (e.g. 204 on success,
    *                 409 on conflict like insufficient stock, 4xx/5xx on errors)
     *
    * @throws \RuntimeException
     */
    public function createOrder(string $customerId, array $items, string $currency = 'EUR'): array
    {
        // Calculate total amount
        $orderAmount = 0.0;
        foreach ($items as $item) {
            $orderAmount += (float)$item['itemAmount'];
        }

        $payload = [
            'order' => [
                'customer'    => $customerId,
                'orderDate'   => date('Y-m-d'),
                'orderAmount' => $orderAmount,
                'currency'    => $currency,
                'items'       => [],
            ],
        ];

        $itemId = 1;
        foreach ($items as $item) {
            $payload['order']['items'][] = [
                'itemID'     => $itemId++,
                'product'    => $item['product'],          // UUID aus ERP (Products.ID)
                'quantity'   => (int)$item['quantity'],
                'itemAmount' => (float)$item['itemAmount'],
                'currency'   => $item['currency'] ?? $currency,
            ];
        }

        // POST /rest/api/createOrder
        return $this->request('POST', '/createOrder', $payload);
    }
}
