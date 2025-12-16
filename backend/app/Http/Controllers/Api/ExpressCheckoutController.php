<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExpressCheckoutPreference;
use App\Models\SavedAddress;
use App\Models\SavedPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpressCheckoutController extends Controller
{
    /**
     * Get express checkout preferences
     */
    public function getPreferences()
    {
        $preferences = ExpressCheckoutPreference::with([
                'defaultPaymentMethod',
                'defaultShippingAddress',
                'defaultBillingAddress'
            ])
            ->where('user_id', Auth::id())
            ->first();

        if (!$preferences) {
            $preferences = ExpressCheckoutPreference::create([
                'user_id' => Auth::id(),
                'enabled' => false,
            ]);
        }

        return response()->json($preferences);
    }

    /**
     * Update express checkout preferences
     */
    public function updatePreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'sometimes|boolean',
            'default_payment_method_id' => 'nullable|exists:saved_payment_methods,id',
            'default_shipping_address_id' => 'nullable|exists:saved_addresses,id',
            'default_billing_address_id' => 'nullable|exists:saved_addresses,id',
            'skip_review_step' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $preferences = ExpressCheckoutPreference::firstOrCreate(
            ['user_id' => Auth::id()],
            ['enabled' => false]
        );

        $preferences->update($request->only([
            'enabled',
            'default_payment_method_id',
            'default_shipping_address_id',
            'default_billing_address_id',
            'skip_review_step'
        ]));

        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => $preferences->load([
                'defaultPaymentMethod',
                'defaultShippingAddress',
                'defaultBillingAddress'
            ]),
        ]);
    }

    /**
     * Get saved payment methods
     */
    public function getPaymentMethods()
    {
        $methods = SavedPaymentMethod::where('user_id', Auth::id())
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($methods);
    }

    /**
     * Add payment method
     */
    public function addPaymentMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:card,paypal,bank_account',
            'provider' => 'required|string',
            'provider_payment_method_id' => 'required|string',
            'last_four' => 'nullable|string|size:4',
            'card_brand' => 'nullable|string',
            'card_holder_name' => 'nullable|string',
            'expiry_month' => 'nullable|integer|min:1|max:12',
            'expiry_year' => 'nullable|integer|min:' . date('Y'),
            'billing_address_id' => 'nullable|exists:saved_addresses,id',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // If setting as default, unset other defaults
            if ($request->is_default) {
                SavedPaymentMethod::where('user_id', Auth::id())
                    ->update(['is_default' => false]);
            }

            $method = SavedPaymentMethod::create(array_merge(
                $request->all(),
                ['user_id' => Auth::id()]
            ));

            DB::commit();

            return response()->json([
                'message' => 'Payment method added successfully',
                'method' => $method,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to add payment method'], 500);
        }
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod($methodId)
    {
        $method = SavedPaymentMethod::where('id', $methodId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $method->delete();

        return response()->json(['message' => 'Payment method deleted successfully']);
    }

    /**
     * Set default payment method
     */
    public function setDefaultPaymentMethod($methodId)
    {
        $method = SavedPaymentMethod::where('id', $methodId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        DB::beginTransaction();
        try {
            SavedPaymentMethod::where('user_id', Auth::id())
                ->update(['is_default' => false]);

            $method->update(['is_default' => true]);

            DB::commit();

            return response()->json(['message' => 'Default payment method updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update default'], 500);
        }
    }

    /**
     * Get saved addresses
     */
    public function getAddresses(Request $request)
    {
        $type = $request->get('type'); // shipping, billing, both

        $query = SavedAddress::where('user_id', Auth::id());

        if ($type) {
            if ($type === 'shipping') {
                $query->shipping();
            } elseif ($type === 'billing') {
                $query->billing();
            }
        }

        $addresses = $query->orderBy('is_default_shipping', 'desc')
            ->orderBy('is_default_billing', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($addresses);
    }

    /**
     * Add address
     */
    public function addAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:shipping,billing,both',
            'label' => 'nullable|string',
            'full_name' => 'required|string',
            'phone' => 'required|string',
            'address_line_1' => 'required|string',
            'address_line_2' => 'nullable|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'postal_code' => 'required|string',
            'country' => 'required|string',
            'is_default_shipping' => 'sometimes|boolean',
            'is_default_billing' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // If setting as default, unset other defaults
            if ($request->is_default_shipping) {
                SavedAddress::where('user_id', Auth::id())
                    ->update(['is_default_shipping' => false]);
            }

            if ($request->is_default_billing) {
                SavedAddress::where('user_id', Auth::id())
                    ->update(['is_default_billing' => false]);
            }

            $address = SavedAddress::create(array_merge(
                $request->all(),
                ['user_id' => Auth::id()]
            ));

            DB::commit();

            return response()->json([
                'message' => 'Address added successfully',
                'address' => $address,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to add address'], 500);
        }
    }

    /**
     * Update address
     */
    public function updateAddress(Request $request, $addressId)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:shipping,billing,both',
            'label' => 'nullable|string',
            'full_name' => 'sometimes|string',
            'phone' => 'sometimes|string',
            'address_line_1' => 'sometimes|string',
            'address_line_2' => 'nullable|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'postal_code' => 'sometimes|string',
            'country' => 'sometimes|string',
            'is_default_shipping' => 'sometimes|boolean',
            'is_default_billing' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $address = SavedAddress::where('id', $addressId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // If setting as default, unset other defaults
            if ($request->is_default_shipping && !$address->is_default_shipping) {
                SavedAddress::where('user_id', Auth::id())
                    ->where('id', '!=', $addressId)
                    ->update(['is_default_shipping' => false]);
            }

            if ($request->is_default_billing && !$address->is_default_billing) {
                SavedAddress::where('user_id', Auth::id())
                    ->where('id', '!=', $addressId)
                    ->update(['is_default_billing' => false]);
            }

            $address->update($request->all());

            DB::commit();

            return response()->json([
                'message' => 'Address updated successfully',
                'address' => $address,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update address'], 500);
        }
    }

    /**
     * Delete address
     */
    public function deleteAddress($addressId)
    {
        $address = SavedAddress::where('id', $addressId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $address->delete();

        return response()->json(['message' => 'Address deleted successfully']);
    }

    /**
     * Express checkout - create order with saved preferences
     */
    public function expressCheckout(Request $request)
    {
        $preferences = ExpressCheckoutPreference::where('user_id', Auth::id())
            ->with(['defaultPaymentMethod', 'defaultShippingAddress', 'defaultBillingAddress'])
            ->first();

        if (!$preferences || !$preferences->isFullyConfigured()) {
            return response()->json(['error' => 'Express checkout not fully configured'], 400);
        }

        // TODO: Create order using saved preferences
        // This would integrate with your existing order creation logic

        return response()->json([
            'message' => 'Order created successfully',
            'preferences' => $preferences,
        ]);
    }
}
