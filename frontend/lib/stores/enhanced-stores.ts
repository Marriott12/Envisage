import { create } from 'zustand';
import { persist, devtools } from 'zustand/middleware';
import { immer } from 'zustand/middleware/immer';

// Enhanced Cart Store with Immer for immutability
interface CartItem {
  id: number;
  listing_id: number;
  title: string;
  price: number;
  currency: string;
  image: string;
  seller_name: string;
  quantity: number;
  variant?: string;
  options?: Record<string, string>;
}

interface CartStore {
  items: CartItem[];
  isOpen: boolean;
  savedForLater: CartItem[];
  appliedCoupon: string | null;
  discount: number;
  addItem: (item: Omit<CartItem, 'quantity'>) => void;
  removeItem: (listingId: number) => void;
  updateQuantity: (listingId: number, quantity: number) => void;
  saveForLater: (listingId: number) => void;
  moveToCart: (listingId: number) => void;
  clearCart: () => void;
  toggleCart: () => void;
  applyCoupon: (code: string, discount: number) => void;
  removeCoupon: () => void;
  getTotalItems: () => number;
  getTotalPrice: () => number;
  getFinalPrice: () => number;
}

export const useCartStore = create<CartStore>()(
  devtools(
    persist(
      immer((set, get) => ({
        items: [],
        isOpen: false,
        savedForLater: [],
        appliedCoupon: null,
        discount: 0,

        addItem: (item) => {
          set((state) => {
            const existingItem = state.items.find(i => i.listing_id === item.listing_id);
            
            if (existingItem) {
              existingItem.quantity += 1;
            } else {
              state.items.push({ ...item, quantity: 1 });
            }
          });
        },

        removeItem: (listingId) => {
          set((state) => {
            state.items = state.items.filter(item => item.listing_id !== listingId);
          });
        },

        updateQuantity: (listingId, quantity) => {
          if (quantity <= 0) {
            get().removeItem(listingId);
            return;
          }
          
          set((state) => {
            const item = state.items.find(i => i.listing_id === listingId);
            if (item) {
              item.quantity = quantity;
            }
          });
        },

        saveForLater: (listingId) => {
          set((state) => {
            const itemIndex = state.items.findIndex(i => i.listing_id === listingId);
            if (itemIndex !== -1) {
              const item = state.items[itemIndex];
              state.savedForLater.push(item);
              state.items.splice(itemIndex, 1);
            }
          });
        },

        moveToCart: (listingId) => {
          set((state) => {
            const itemIndex = state.savedForLater.findIndex(i => i.listing_id === listingId);
            if (itemIndex !== -1) {
              const item = state.savedForLater[itemIndex];
              state.items.push(item);
              state.savedForLater.splice(itemIndex, 1);
            }
          });
        },

        clearCart: () => {
          set((state) => {
            state.items = [];
            state.appliedCoupon = null;
            state.discount = 0;
          });
        },

        toggleCart: () => {
          set((state) => {
            state.isOpen = !state.isOpen;
          });
        },

        applyCoupon: (code, discount) => {
          set((state) => {
            state.appliedCoupon = code;
            state.discount = discount;
          });
        },

        removeCoupon: () => {
          set((state) => {
            state.appliedCoupon = null;
            state.discount = 0;
          });
        },

        getTotalItems: () => {
          return get().items.reduce((total, item) => total + item.quantity, 0);
        },

        getTotalPrice: () => {
          return get().items.reduce((total, item) => total + (item.price * item.quantity), 0);
        },

        getFinalPrice: () => {
          const total = get().getTotalPrice();
          const discount = get().discount;
          return total - (total * discount / 100);
        },
      })),
      {
        name: 'cart-store',
        version: 1,
      }
    ),
    { name: 'CartStore' }
  )
);

// Enhanced Filters Store
interface FilterStore {
  category: string;
  condition: string;
  minPrice: number | null;
  maxPrice: number | null;
  location: string;
  sortBy: string;
  inStock: boolean;
  rating: number | null;
  brands: string[];
  tags: string[];
  savedFilters: Array<{ name: string; filters: Record<string, any> }>;
  setFilter: <K extends keyof Omit<FilterStore, 'setFilter' | 'resetFilters' | 'saveFilterPreset' | 'applyFilterPreset' | 'deleteFilterPreset' | 'savedFilters'>>(
    key: K,
    value: FilterStore[K]
  ) => void;
  resetFilters: () => void;
  saveFilterPreset: (name: string) => void;
  applyFilterPreset: (name: string) => void;
  deleteFilterPreset: (name: string) => void;
}

export const useFilterStore = create<FilterStore>()(
  devtools(
    persist(
      immer((set, get) => ({
        category: '',
        condition: '',
        minPrice: null,
        maxPrice: null,
        location: '',
        sortBy: 'newest',
        inStock: false,
        rating: null,
        brands: [],
        tags: [],
        savedFilters: [],

        setFilter: (key, value) => {
          set((state) => {
            (state as any)[key] = value;
          });
        },

        resetFilters: () => {
          set((state) => {
            state.category = '';
            state.condition = '';
            state.minPrice = null;
            state.maxPrice = null;
            state.location = '';
            state.sortBy = 'newest';
            state.inStock = false;
            state.rating = null;
            state.brands = [];
            state.tags = [];
          });
        },

        saveFilterPreset: (name) => {
          set((state) => {
            const currentFilters = {
              category: state.category,
              condition: state.condition,
              minPrice: state.minPrice,
              maxPrice: state.maxPrice,
              location: state.location,
              sortBy: state.sortBy,
              inStock: state.inStock,
              rating: state.rating,
              brands: state.brands,
              tags: state.tags,
            };
            
            const existingIndex = state.savedFilters.findIndex(f => f.name === name);
            if (existingIndex !== -1) {
              state.savedFilters[existingIndex].filters = currentFilters;
            } else {
              state.savedFilters.push({ name, filters: currentFilters });
            }
          });
        },

        applyFilterPreset: (name) => {
          set((state) => {
            const preset = state.savedFilters.find(f => f.name === name);
            if (preset) {
              Object.assign(state, preset.filters);
            }
          });
        },

        deleteFilterPreset: (name) => {
          set((state) => {
            state.savedFilters = state.savedFilters.filter(f => f.name !== name);
          });
        },
      })),
      {
        name: 'filter-store',
        version: 1,
        partialize: (state) => ({
          savedFilters: state.savedFilters,
          sortBy: state.sortBy,
        }),
      }
    ),
    { name: 'FilterStore' }
  )
);

// Checkout Store for multi-step process
interface CheckoutStore {
  step: number;
  shippingAddress: {
    fullName: string;
    address: string;
    city: string;
    state: string;
    zipCode: string;
    country: string;
    phone: string;
  } | null;
  billingAddress: {
    fullName: string;
    address: string;
    city: string;
    state: string;
    zipCode: string;
    country: string;
  } | null;
  useSameAddress: boolean;
  shippingMethod: string | null;
  paymentMethod: string | null;
  giftWrap: boolean;
  giftMessage: string;
  orderNotes: string;
  savedAddresses: Array<{ id: string; label: string; address: any }>;
  setStep: (step: number) => void;
  nextStep: () => void;
  prevStep: () => void;
  setShippingAddress: (address: CheckoutStore['shippingAddress']) => void;
  setBillingAddress: (address: CheckoutStore['billingAddress']) => void;
  setUseSameAddress: (value: boolean) => void;
  setShippingMethod: (method: string) => void;
  setPaymentMethod: (method: string) => void;
  setGiftOptions: (giftWrap: boolean, message?: string) => void;
  setOrderNotes: (notes: string) => void;
  saveAddress: (label: string, address: any) => void;
  reset: () => void;
}

export const useCheckoutStore = create<CheckoutStore>()(
  devtools(
    persist(
      immer((set, get) => ({
        step: 1,
        shippingAddress: null,
        billingAddress: null,
        useSameAddress: true,
        shippingMethod: null,
        paymentMethod: null,
        giftWrap: false,
        giftMessage: '',
        orderNotes: '',
        savedAddresses: [],

        setStep: (step) => {
          set((state) => {
            state.step = step;
          });
        },

        nextStep: () => {
          set((state) => {
            state.step = Math.min(state.step + 1, 4);
          });
        },

        prevStep: () => {
          set((state) => {
            state.step = Math.max(state.step - 1, 1);
          });
        },

        setShippingAddress: (address) => {
          set((state) => {
            state.shippingAddress = address;
          });
        },

        setBillingAddress: (address) => {
          set((state) => {
            state.billingAddress = address;
          });
        },

        setUseSameAddress: (value) => {
          set((state) => {
            state.useSameAddress = value;
          });
        },

        setShippingMethod: (method) => {
          set((state) => {
            state.shippingMethod = method;
          });
        },

        setPaymentMethod: (method) => {
          set((state) => {
            state.paymentMethod = method;
          });
        },

        setGiftOptions: (giftWrap, message = '') => {
          set((state) => {
            state.giftWrap = giftWrap;
            state.giftMessage = message;
          });
        },

        setOrderNotes: (notes) => {
          set((state) => {
            state.orderNotes = notes;
          });
        },

        saveAddress: (label, address) => {
          set((state) => {
            const id = `addr_${Date.now()}`;
            state.savedAddresses.push({ id, label, address });
          });
        },

        reset: () => {
          set((state) => {
            state.step = 1;
            state.shippingAddress = null;
            state.billingAddress = null;
            state.useSameAddress = true;
            state.shippingMethod = null;
            state.paymentMethod = null;
            state.giftWrap = false;
            state.giftMessage = '';
            state.orderNotes = '';
          });
        },
      })),
      {
        name: 'checkout-store',
        version: 1,
        partialize: (state) => ({
          savedAddresses: state.savedAddresses,
        }),
      }
    ),
    { name: 'CheckoutStore' }
  )
);
