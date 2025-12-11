<?php

namespace App\Services;

use Shippo;
use Shippo_Shipment;
use Shippo_Address;
use Shippo_Transaction;

class ShippingService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.shippo.api_key');
        Shippo::setApiKey($this->apiKey);
    }

    /**
     * Validate shipping address
     */
    public function validateAddress($address)
    {
        try {
            $addressData = [
                'name' => $address['name'] ?? '',
                'street1' => $address['street1'],
                'street2' => $address['street2'] ?? '',
                'city' => $address['city'],
                'state' => $address['state'],
                'zip' => $address['zip'],
                'country' => $address['country'],
                'validate' => true,
            ];

            $validatedAddress = Shippo_Address::create($addressData);
            $validationResults = (array) $validatedAddress['validation_results'];

            return [
                'success' => true,
                'valid' => $validationResults['is_valid'] ?? false,
                'address' => $validatedAddress,
                'messages' => $validationResults['messages'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get shipping rates for an order
     */
    public function getShippingRates($fromAddress, $toAddress, $parcel)
    {
        try {
            $shipment = Shippo_Shipment::create([
                'address_from' => $fromAddress,
                'address_to' => $toAddress,
                'parcels' => [$parcel],
                'async' => false,
            ]);

            if ($shipment['status'] === 'SUCCESS') {
                $rates = [];
                foreach ($shipment['rates'] as $rate) {
                    $rates[] = [
                        'object_id' => $rate['object_id'],
                        'provider' => $rate['provider'],
                        'service_level' => $rate['servicelevel']['name'],
                        'amount' => $rate['amount'],
                        'currency' => $rate['currency'],
                        'estimated_days' => $rate['estimated_days'] ?? null,
                        'duration_terms' => $rate['duration_terms'] ?? null,
                    ];
                }

                return [
                    'success' => true,
                    'rates' => $rates,
                    'shipment_id' => $shipment['object_id'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve rates',
                'messages' => $shipment['messages'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Purchase shipping label
     */
    public function purchaseLabel($rateId)
    {
        try {
            $transaction = Shippo_Transaction::create([
                'rate' => $rateId,
                'label_file_type' => 'PDF',
                'async' => false,
            ]);

            if ($transaction['status'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'transaction_id' => $transaction['object_id'],
                    'tracking_number' => $transaction['tracking_number'],
                    'tracking_url' => $transaction['tracking_url_provider'],
                    'label_url' => $transaction['label_url'],
                    'commercial_invoice_url' => $transaction['commercial_invoice_url'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to purchase label',
                'messages' => $transaction['messages'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get tracking information
     */
    public function getTrackingInfo($carrier, $trackingNumber)
    {
        try {
            $tracking = \Shippo_Track::retrieve([
                'carrier' => $carrier,
                'tracking_number' => $trackingNumber,
            ]);

            return [
                'success' => true,
                'carrier' => $tracking['carrier'],
                'tracking_number' => $tracking['tracking_number'],
                'status' => $tracking['tracking_status']['status'],
                'status_details' => $tracking['tracking_status']['status_details'] ?? '',
                'status_date' => $tracking['tracking_status']['status_date'] ?? '',
                'location' => $tracking['tracking_status']['location'] ?? [],
                'tracking_history' => $tracking['tracking_history'] ?? [],
                'eta' => $tracking['eta'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create return label
     */
    public function createReturnLabel($originalShipment, $returnAddress)
    {
        try {
            // Create return shipment (reverse from/to addresses)
            $shipment = Shippo_Shipment::create([
                'address_from' => $originalShipment['address_to'],
                'address_to' => $returnAddress,
                'parcels' => $originalShipment['parcels'],
                'async' => false,
            ]);

            $rates = (array) $shipment['rates'];
            if ($shipment['status'] === 'SUCCESS' && !empty($rates)) {
                // Use the cheapest rate
                $cheapestRate = $rates[0];
                foreach ($rates as $rate) {
                    $rateArray = (array) $rate;
                    $cheapestArray = (array) $cheapestRate;
                    if (floatval($rateArray['amount']) < floatval($cheapestArray['amount'])) {
                        $cheapestRate = $rate;
                    }
                }

                // Purchase return label
                return $this->purchaseLabel($cheapestRate['object_id']);
            }

            return [
                'success' => false,
                'error' => 'Failed to create return shipment',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate shipping cost based on weight and destination
     */
    public function calculateShippingCost($weight, $fromZip, $toZip, $country = 'US')
    {
        // Simplified calculation - in production, use getShippingRates
        $parcel = [
            'length' => '10',
            'width' => '10',
            'height' => '10',
            'distance_unit' => 'in',
            'weight' => $weight,
            'mass_unit' => 'lb',
        ];

        $fromAddress = [
            'zip' => $fromZip,
            'country' => $country,
        ];

        $toAddress = [
            'zip' => $toZip,
            'country' => $country,
        ];

        return $this->getShippingRates($fromAddress, $toAddress, $parcel);
    }

    /**
     * Batch create shipping labels
     */
    public function batchCreateLabels($shipments)
    {
        $results = [];

        foreach ($shipments as $shipment) {
            $rates = $this->getShippingRates(
                $shipment['from_address'],
                $shipment['to_address'],
                $shipment['parcel']
            );

            if ($rates['success'] && !empty($rates['rates'])) {
                $label = $this->purchaseLabel($rates['rates'][0]['object_id']);
                $results[] = array_merge($shipment, ['label' => $label]);
            } else {
                $results[] = array_merge($shipment, ['label' => $rates]);
            }
        }

        return $results;
    }
}
