<?php

namespace App\Libraries;

/**
 * Handles interactions with third-party cryptocurrency APIs to fetch blockchain data.
 */
class CryptoService
{
    /**
     * Fetches the final balance for a given Bitcoin address.
     *
     * @param string $address The Bitcoin address to query.
     * @return array An array containing the balance data or an error message.
     */
    public function getBtcBalance($address)
    {
        $url = "https://blockchain.info/balance?active=" . urlencode($address);
        $data = $this->makeApiRequest($url);

        if (isset($data[$address]['final_balance'])) {
            $balance_satoshi = $data[$address]['final_balance'];
            $balance_btc = $balance_satoshi / 100000000;
            return [
                'asset' => 'Bitcoin (BTC)',
                'address' => $address,
                'query' => 'Final Balance',
                'balance' => $balance_btc . ' BTC'
            ];
        } else {
            return ['error' => 'Could not retrieve BTC balance for the specified address.'];
        }
    }

    /**
     * Fetches recent transactions for a given Bitcoin address.
     *
     * @param string $address The Bitcoin address to query.
     * @param int    $limit   The maximum number of transactions to return.
     * @return array An array containing transaction data or an error message.
     */
    public function getBtcTransactions($address, $limit)
    {
        $url = "https://blockchain.info/rawaddr/" . urlencode($address) . "?limit=" . $limit;
        $data = $this->makeApiRequest($url);

        if (isset($data['txs'])) {
            $transactions = [];
            foreach ($data['txs'] as $tx) {
                $sending_addresses = [];
                foreach ($tx['inputs'] as $input) {
                    if (isset($input['prev_out']['addr'])) {
                        $sending_addresses[] = $input['prev_out']['addr'];
                    }
                }
                $sending_addresses = array_unique($sending_addresses); // Ensure unique sending addresses

                $receiving_addresses = [];
                foreach ($tx['out'] as $output) {
                    if (isset($output['addr'])) {
                        $amount = $output['value'] / 100000000;
                        $receiving_addresses[] = [
                            'address' => $output['addr'],
                            'amount' => rtrim(rtrim(sprintf('%.8f', $amount), '0'), '.') . ' BTC'
                        ];
                    }
                }

                $transactions[] = [
                    'hash' => $tx['hash'],
                    'time' => date("Y-m-d H:i:s", $tx['time']) . " UTC",
                    'block_height' => $tx['block_height'] ?? 'N/A', // Handle missing block_height
                    'fee' => rtrim(rtrim(sprintf('%.8f', ($tx['fee'] / 100000000)), '0'), '.') . ' BTC',
                    'sending_addresses' => $sending_addresses,
                    'receiving_addresses' => $receiving_addresses
                ];
            }
            return [
                'asset' => 'Bitcoin (BTC)',
                'address' => $address,
                'query' => 'Last ' . count($transactions) . ' Detailed Transactions',
                'transactions' => $transactions
            ];
        } else {
            return ['error' => 'Could not retrieve BTC transactions for the specified address.'];
        }
    }

    /**
     * Fetches the final balance for a given Litecoin address.
     *
     * @param string $address The Litecoin address to query.
     * @return array An array containing the balance data or an error message.
     */
    public function getLtcBalance($address)
    {
        $url = "https://api.blockchair.com/litecoin/dashboards/address/" . urlencode($address) . "?limit=1"; // Add limit=1 to ensure full data structure
        $data = $this->makeApiRequest($url);

        if (isset($data['data']) && !empty($data['data'])) {
            $address_data = reset($data['data']); // Get the first element, regardless of its key
            if (isset($address_data['address']['balance'])) {
                $balance_litoshi = $address_data['address']['balance'];
                $balance_ltc = $balance_litoshi / 100000000;
                return [
                    'asset' => 'Litecoin (LTC)',
                    'address' => $address,
                    'query' => 'Final Balance',
                    'balance' => $balance_ltc . ' LTC'
                ];
            }
        }
        return ['error' => 'Could not retrieve LTC balance for the specified address.'];
    }

    /**
     * Fetches recent detailed transactions for a Litecoin address.
     *
     * @param string $address The Litecoin address to query.
     * @param int    $limit   The maximum number of transactions to return.
     * @return array An array containing transaction data or an error message.
     */
    public function getLtcTransactions($address, $limit)
    {
        // Step 1: Get the list of transaction hashes
        $hashes_url = "https://api.blockchair.com/litecoin/dashboards/address/" . urlencode($address) . "?limit=" . $limit;
        $hashes_data = $this->makeApiRequest($hashes_url);

        if (isset($hashes_data['data']) && !empty($hashes_data['data'])) {
            $address_data = reset($hashes_data['data']); // Get the first element, regardless of its key
            if (isset($address_data['transactions'])) {
                $tx_hashes = $address_data['transactions'];
                $transactions = [];
            } else {
                return ['error' => 'Could not retrieve LTC transaction list for the specified address.'];
            }
        } else {
            return ['error' => 'Could not retrieve LTC transaction list for the specified address.'];
        }

        // Step 2: Loop through hashes and get details for each one
        foreach ($tx_hashes as $hash) {
            $details_url = "https://api.blockchair.com/litecoin/dashboards/transaction/" . $hash;
            $details_data = $this->makeApiRequest($details_url);

            if (!isset($details_data['data'][$hash]['transaction'])) {
                log_message('warning', "Could not fetch details for LTC transaction hash: {$hash}");
                continue; // Skip to the next transaction if details can't be fetched
            }

            $tx = $details_data['data'][$hash];

            $sending_addresses = [];
            foreach ($tx['inputs'] as $input) {
                $sending_addresses[] = $input['recipient'];
            }

            $receiving_addresses = [];
            foreach ($tx['outputs'] as $output) {
                $amount = $output['value'] / 100000000;
                $receiving_addresses[] = [
                    'address' => $output['recipient'],
                    'amount' => rtrim(rtrim(sprintf('%.8f', $amount), '0'), '.') . ' LTC'
                ];
            }

            $transactions[] = [
                'hash' => $tx['transaction']['hash'],
                'time' => $tx['transaction']['time'] . " UTC",
                'block_id' => $tx['transaction']['block_id'],
                'fee' => rtrim(rtrim(sprintf('%.8f', ($tx['transaction']['fee'] / 100000000)), '0'), '.') . ' LTC',
                'sending_addresses' => $sending_addresses,
                'receiving_addresses' => $receiving_addresses
            ];
        }

        return [
            'asset' => 'Litecoin (LTC)',
            'address' => $address,
            'query' => 'Last ' . count($transactions) . ' Detailed Transactions',
            'transactions' => $transactions
        ];
    }

    /**
     * Executes a GET request to a specified API endpoint using CURLRequest.
     *
     * @param string $url The full URL of the API endpoint.
     * @return array The decoded JSON response as an associative array.
     * @throws \CodeIgniter\HTTP\Exceptions\HTTPException If an HTTP error occurs during the request.
     * @throws \Exception If any other unexpected error occurs during the API request.
     */
    private function makeApiRequest(string $url): array
    {
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->get($url, [
                'headers' => [
                    'User-Agent' => 'My-PHP-Crypto-Checker/1.0',
                ],
                'timeout' => 10, // Set a timeout for the request
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data === null) {
                log_message('error', 'Failed to decode JSON response from API: ' . $response->getBody());
                return ['error' => 'Failed to decode API response.'];
            }

            return $data;
        } catch (\CodeIgniter\HTTP\Exceptions\HTTPException $e) {
            log_message('error', 'Crypto API HTTP Error: ' . $e->getMessage());
            // Re-throw or return an error structure that indicates the specific exception type
            throw $e;
        } catch (\Exception $e) {
            log_message('error', 'Crypto API Error: ' . $e->getMessage());
            // Re-throw or return an error structure that indicates the specific exception type
            throw $e;
        }
    }
}
