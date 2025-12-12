'use client';

import { useState, useEffect } from 'react';
import { useCheckoutStore } from '@/lib/stores/enhanced-stores';
import { MapPin, Search, Loader2, Check } from 'lucide-react';

interface Address {
  id?: string;
  firstName: string;
  lastName: string;
  street: string;
  apartment?: string;
  city: string;
  state: string;
  postalCode: string;
  country: string;
  phone: string;
  isDefault?: boolean;
}

interface AddressFormProps {
  type?: 'shipping' | 'billing';
  onSave?: (address: Address) => void;
  initialAddress?: Address;
  showSavedAddresses?: boolean;
  className?: string;
}

interface AddressPrediction {
  placeId: string;
  description: string;
  mainText: string;
  secondaryText: string;
}

export function AddressForm({
  type = 'shipping',
  onSave,
  initialAddress,
  showSavedAddresses = true,
  className = '',
}: AddressFormProps) {
  const checkout = useCheckoutStore();
  
  const [formData, setFormData] = useState<Address>(
    initialAddress || {
      firstName: '',
      lastName: '',
      street: '',
      apartment: '',
      city: '',
      state: '',
      postalCode: '',
      country: 'United States',
      phone: '',
    }
  );

  const [searchQuery, setSearchQuery] = useState('');
  const [predictions, setPredictions] = useState<AddressPrediction[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [showPredictions, setShowPredictions] = useState(false);
  const [savedAddresses, setSavedAddresses] = useState<Address[]>([]);
  const [selectedSavedAddress, setSelectedSavedAddress] = useState<string | null>(null);
  const [useShippingForBilling, setUseShippingForBilling] = useState(true);

  // Load saved addresses
  useEffect(() => {
    if (showSavedAddresses) {
      fetchSavedAddresses();
    }
  }, [showSavedAddresses]);

  const fetchSavedAddresses = async () => {
    try {
      const response = await fetch('/api/user/addresses');
      if (response.ok) {
        const data = await response.json();
        setSavedAddresses(data.addresses || []);
      }
    } catch (error) {
      console.error('Failed to fetch saved addresses:', error);
    }
  };

  // Address autocomplete
  const handleAddressSearch = async (query: string) => {
    setSearchQuery(query);
    
    if (query.length < 3) {
      setPredictions([]);
      return;
    }

    setIsSearching(true);
    try {
      const response = await fetch(
        `/api/address/autocomplete?query=${encodeURIComponent(query)}`
      );
      
      if (response.ok) {
        const data = await response.json();
        setPredictions(data.predictions || []);
        setShowPredictions(true);
      }
    } catch (error) {
      console.error('Address autocomplete failed:', error);
    } finally {
      setIsSearching(false);
    }
  };

  const handleSelectPrediction = async (prediction: AddressPrediction) => {
    try {
      const response = await fetch(
        `/api/address/details?placeId=${prediction.placeId}`
      );
      
      if (response.ok) {
        const data = await response.json();
        setFormData({
          ...formData,
          street: data.street || '',
          city: data.city || '',
          state: data.state || '',
          postalCode: data.postalCode || '',
          country: data.country || 'United States',
        });
        setSearchQuery(prediction.description);
        setShowPredictions(false);
      }
    } catch (error) {
      console.error('Failed to fetch address details:', error);
    }
  };

  const handleSelectSavedAddress = (address: Address) => {
    setFormData(address);
    setSelectedSavedAddress(address.id || null);
  };

  const handleInputChange = (field: keyof Address, value: string) => {
    setFormData({ ...formData, [field]: value });
    setSelectedSavedAddress(null);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    const storeAddress = {
      fullName: `${formData.firstName} ${formData.lastName}`,
      address: formData.apartment ? `${formData.street}, ${formData.apartment}` : formData.street,
      city: formData.city,
      state: formData.state,
      zipCode: formData.postalCode,
      country: formData.country,
      phone: formData.phone,
    };

    if (type === 'shipping') {
      checkout.setShippingAddress(storeAddress);
      if (useShippingForBilling) {
        checkout.setBillingAddress(storeAddress);
      }
    } else {
      checkout.setBillingAddress(storeAddress);
    }

    if (onSave) {
      onSave(formData);
    }
  };

  return (
    <div className={className}>
      {/* Saved Addresses */}
      {showSavedAddresses && savedAddresses.length > 0 && (
        <div className="mb-6">
          <h3 className="text-lg font-semibold mb-3">Saved Addresses</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            {savedAddresses.map((address) => (
              <button
                key={address.id}
                onClick={() => handleSelectSavedAddress(address)}
                className={`
                  p-4 text-left rounded-lg border-2 transition-all
                  ${
                    selectedSavedAddress === address.id
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  }
                `}
              >
                <div className="flex items-start justify-between">
                  <div className="text-sm">
                    <p className="font-semibold">
                      {address.firstName} {address.lastName}
                    </p>
                    <p className="text-gray-600 mt-1">{address.street}</p>
                    {address.apartment && (
                      <p className="text-gray-600">{address.apartment}</p>
                    )}
                    <p className="text-gray-600">
                      {address.city}, {address.state} {address.postalCode}
                    </p>
                  </div>
                  {selectedSavedAddress === address.id && (
                    <Check className="w-5 h-5 text-blue-500" />
                  )}
                </div>
                {address.isDefault && (
                  <span className="inline-block mt-2 px-2 py-1 text-xs bg-gray-100 rounded">
                    Default
                  </span>
                )}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Address Form */}
      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Address Search */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Search Address
          </label>
          <div className="relative">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => handleAddressSearch(e.target.value)}
              placeholder="Start typing your address..."
              className="
                w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg
                focus:ring-2 focus:ring-blue-500 focus:border-transparent
              "
            />
            <MapPin className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
            {isSearching && (
              <Loader2 className="absolute right-3 top-3 w-5 h-5 text-gray-400 animate-spin" />
            )}
          </div>

          {/* Predictions Dropdown */}
          {showPredictions && predictions.length > 0 && (
            <div className="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto">
              {predictions.map((prediction) => (
                <button
                  key={prediction.placeId}
                  type="button"
                  onClick={() => handleSelectPrediction(prediction)}
                  className="
                    w-full px-4 py-3 text-left hover:bg-gray-50
                    border-b border-gray-100 last:border-b-0
                  "
                >
                  <p className="font-medium text-sm">{prediction.mainText}</p>
                  <p className="text-xs text-gray-500">{prediction.secondaryText}</p>
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Name Fields */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              First Name *
            </label>
            <input
              type="text"
              required
              value={formData.firstName}
              onChange={(e) => handleInputChange('firstName', e.target.value)}
              className="
                w-full px-4 py-2 border border-gray-300 rounded-lg
                focus:ring-2 focus:ring-blue-500 focus:border-transparent
              "
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Last Name *
            </label>
            <input
              type="text"
              required
              value={formData.lastName}
              onChange={(e) => handleInputChange('lastName', e.target.value)}
              className="
                w-full px-4 py-2 border border-gray-300 rounded-lg
                focus:ring-2 focus:ring-blue-500 focus:border-transparent
              "
            />
          </div>
        </div>

        {/* Street Address */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Street Address *
          </label>
          <input
            type="text"
            required
            value={formData.street}
            onChange={(e) => handleInputChange('street', e.target.value)}
            className="
              w-full px-4 py-2 border border-gray-300 rounded-lg
              focus:ring-2 focus:ring-blue-500 focus:border-transparent
            "
          />
        </div>

        {/* Apartment/Suite */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Apartment, Suite, etc. (optional)
          </label>
          <input
            type="text"
            value={formData.apartment}
            onChange={(e) => handleInputChange('apartment', e.target.value)}
            className="
              w-full px-4 py-2 border border-gray-300 rounded-lg
              focus:ring-2 focus:ring-blue-500 focus:border-transparent
            "
          />
        </div>

        {/* City, State, Postal Code */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              City *
            </label>
            <input
              type="text"
              required
              value={formData.city}
              onChange={(e) => handleInputChange('city', e.target.value)}
              className="
                w-full px-4 py-2 border border-gray-300 rounded-lg
                focus:ring-2 focus:ring-blue-500 focus:border-transparent
              "
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              State *
            </label>
            <input
              type="text"
              required
              value={formData.state}
              onChange={(e) => handleInputChange('state', e.target.value)}
              className="
                w-full px-4 py-2 border border-gray-300 rounded-lg
                focus:ring-2 focus:ring-blue-500 focus:border-transparent
              "
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Postal Code *
            </label>
            <input
              type="text"
              required
              value={formData.postalCode}
              onChange={(e) => handleInputChange('postalCode', e.target.value)}
              className="
                w-full px-4 py-2 border border-gray-300 rounded-lg
                focus:ring-2 focus:ring-blue-500 focus:border-transparent
              "
            />
          </div>
        </div>

        {/* Country */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Country *
          </label>
          <select
            required
            value={formData.country}
            onChange={(e) => handleInputChange('country', e.target.value)}
            className="
              w-full px-4 py-2 border border-gray-300 rounded-lg
              focus:ring-2 focus:ring-blue-500 focus:border-transparent
            "
          >
            <option value="United States">United States</option>
            <option value="Canada">Canada</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="Australia">Australia</option>
            <option value="Germany">Germany</option>
            <option value="France">France</option>
          </select>
        </div>

        {/* Phone */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Phone Number *
          </label>
          <input
            type="tel"
            required
            value={formData.phone}
            onChange={(e) => handleInputChange('phone', e.target.value)}
            className="
              w-full px-4 py-2 border border-gray-300 rounded-lg
              focus:ring-2 focus:ring-blue-500 focus:border-transparent
            "
          />
        </div>

        {/* Use Shipping for Billing (only show for shipping form) */}
        {type === 'shipping' && (
          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="useShippingForBilling"
              checked={useShippingForBilling}
              onChange={(e) => setUseShippingForBilling(e.target.checked)}
              className="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
            />
            <label htmlFor="useShippingForBilling" className="text-sm text-gray-700">
              Billing address same as shipping
            </label>
          </div>
        )}

        {/* Submit Button */}
        <button
          type="submit"
          className="
            w-full py-3 bg-blue-600 text-white rounded-lg font-medium
            hover:bg-blue-700 transition-colors
          "
        >
          Save {type === 'shipping' ? 'Shipping' : 'Billing'} Address
        </button>
      </form>
    </div>
  );
}

// Compact variant
export function QuickAddressForm({ onSave, className = '' }: Omit<AddressFormProps, 'showSavedAddresses'>) {
  return (
    <AddressForm
      onSave={onSave}
      showSavedAddresses={false}
      className={className}
    />
  );
}
