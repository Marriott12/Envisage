'use client';

import { Fragment } from 'react';
import { Listbox, Transition } from '@headlessui/react';
import { CheckIcon, ChevronDownIcon, GlobeAltIcon } from '@heroicons/react/24/outline';
import { useCurrency } from '@/contexts/CurrencyContext';

interface CurrencySwitcherProps {
  variant?: 'dropdown' | 'compact';
  className?: string;
}

export default function CurrencySwitcher({ variant = 'dropdown', className = '' }: CurrencySwitcherProps) {
  const { selectedCurrency, currencies, loading, setSelectedCurrency } = useCurrency();

  if (loading || !selectedCurrency) {
    return (
      <div className={`flex items-center gap-2 text-gray-400 ${className}`}>
        <GlobeAltIcon className="w-5 h-5" />
        <span className="text-sm">Loading...</span>
      </div>
    );
  }

  if (variant === 'compact') {
    return (
      <div className={`relative ${className}`}>
        <Listbox value={selectedCurrency} onChange={setSelectedCurrency}>
          <div className="relative">
            <Listbox.Button className="relative w-full cursor-pointer rounded-lg bg-white py-2 pl-3 pr-10 text-left shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 sm:text-sm">
              <span className="flex items-center gap-2">
                <GlobeAltIcon className="h-5 w-5 text-gray-400" />
                <span className="block truncate font-medium">{selectedCurrency.code}</span>
              </span>
              <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <ChevronDownIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
              </span>
            </Listbox.Button>

            <Transition
              as={Fragment}
              leave="transition ease-in duration-100"
              leaveFrom="opacity-100"
              leaveTo="opacity-0"
            >
              <Listbox.Options className="absolute z-50 mt-1 max-h-60 w-full min-w-[200px] overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                {currencies.map((currency) => (
                  <Listbox.Option
                    key={currency.id}
                    className={({ active }) =>
                      `relative cursor-pointer select-none py-2 pl-3 pr-9 ${
                        active ? 'bg-primary-50 text-primary-900' : 'text-gray-900'
                      }`
                    }
                    value={currency}
                  >
                    {({ selected }) => (
                      <>
                        <div className="flex items-center justify-between">
                          <div>
                            <span className={`block truncate ${selected ? 'font-semibold' : 'font-normal'}`}>
                              {currency.code} - {currency.symbol}
                            </span>
                            <span className="block truncate text-xs text-gray-500">
                              {currency.name}
                            </span>
                          </div>
                          {selected && (
                            <span className="text-primary-600">
                              <CheckIcon className="h-5 w-5" aria-hidden="true" />
                            </span>
                          )}
                        </div>
                      </>
                    )}
                  </Listbox.Option>
                ))}
              </Listbox.Options>
            </Transition>
          </div>
        </Listbox>
      </div>
    );
  }

  // Full dropdown variant (default)
  return (
    <div className={`relative ${className}`}>
      <Listbox value={selectedCurrency} onChange={setSelectedCurrency}>
        <div className="relative">
          <Listbox.Button className="relative w-full cursor-pointer rounded-lg bg-white py-2.5 pl-4 pr-10 text-left shadow-md ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <span className="flex items-center gap-3">
              <GlobeAltIcon className="h-6 w-6 text-gray-500" />
              <div>
                <span className="block text-sm font-semibold text-gray-900">
                  {selectedCurrency.symbol} {selectedCurrency.code}
                </span>
                <span className="block text-xs text-gray-500">{selectedCurrency.name}</span>
              </div>
            </span>
            <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
              <ChevronDownIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
            </span>
          </Listbox.Button>

          <Transition
            as={Fragment}
            leave="transition ease-in duration-100"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <Listbox.Options className="absolute z-50 mt-2 max-h-80 w-full min-w-[280px] overflow-auto rounded-lg bg-white py-1 shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none">
              <div className="px-3 py-2 bg-gray-50 border-b">
                <p className="text-xs font-semibold text-gray-700 uppercase tracking-wide">
                  Select Currency
                </p>
              </div>
              {currencies.map((currency) => (
                <Listbox.Option
                  key={currency.id}
                  className={({ active }) =>
                    `relative cursor-pointer select-none py-3 px-4 ${
                      active ? 'bg-primary-50' : ''
                    }`
                  }
                  value={currency}
                >
                  {({ selected, active }) => (
                    <div className="flex items-center justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-3">
                          <span className="text-2xl">{currency.symbol}</span>
                          <div>
                            <p className={`text-sm font-medium ${selected ? 'text-primary-600' : 'text-gray-900'}`}>
                              {currency.code}
                            </p>
                            <p className="text-xs text-gray-500">{currency.name}</p>
                          </div>
                        </div>
                      </div>
                      {selected && (
                        <CheckIcon className="h-5 w-5 text-primary-600 flex-shrink-0" aria-hidden="true" />
                      )}
                    </div>
                  )}
                </Listbox.Option>
              ))}
            </Listbox.Options>
          </Transition>
        </div>
      </Listbox>
    </div>
  );
}
