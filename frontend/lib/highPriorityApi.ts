import axios from 'axios';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

const getAuthHeader = () => {
  const token = localStorage.getItem('token');
  return token ? { Authorization: `Bearer ${token}` } : {};
};

// ============= CURRENCY APIs =============
export const currencyApi = {
  list: async () => {
    const response = await axios.get(`${API_URL}/currencies`);
    return response.data;
  },

  convert: async (amount: number, from: string, to: string) => {
    const response = await axios.post(
      `${API_URL}/currencies/convert`,
      { amount, from, to },
      { headers: getAuthHeader() }
    );
    return response.data;
  },

  getRates: async (from?: string, to?: string[]) => {
    const params = new URLSearchParams();
    if (from) params.append('from', from);
    if (to) to.forEach(t => params.append('to[]', t));
    
    const response = await axios.get(`${API_URL}/currencies/rates?${params}`, {
      headers: getAuthHeader(),
    });
    return response.data;
  },

  getUserPreference: async () => {
    const response = await axios.get(`${API_URL}/currencies/user-preference`, {
      headers: getAuthHeader(),
    });
    return response.data;
  },

  setUserPreference: async (currency: string) => {
    const response = await axios.put(
      `${API_URL}/currencies/user-preference`,
      { currency },
      { headers: getAuthHeader() }
    );
    return response.data;
  },
};

// ============= INVOICE APIs =============
export const invoiceApi = {
  list: async (params?: {
    status?: string;
    overdue?: boolean;
    from_date?: string;
    to_date?: string;
    page?: number;
  }) => {
    const queryParams = new URLSearchParams();
    if (params?.status) queryParams.append('status', params.status);
    if (params?.overdue !== undefined) queryParams.append('overdue', String(params.overdue));
    if (params?.from_date) queryParams.append('from_date', params.from_date);
    if (params?.to_date) queryParams.append('to_date', params.to_date);
    if (params?.page) queryParams.append('page', String(params.page));

    const response = await axios.get(`${API_URL}/invoices?${queryParams}`, {
      headers: getAuthHeader(),
    });
    return response.data;
  },

  get: async (id: number) => {
    const response = await axios.get(`${API_URL}/invoices/${id}`, {
      headers: getAuthHeader(),
    });
    return response.data;
  },

  generate: async (orderId: number, data?: { notes?: string; due_days?: number }) => {
    const response = await axios.post(
      `${API_URL}/invoices/generate/${orderId}`,
      data,
      { headers: getAuthHeader() }
    );
    return response.data;
  },

  download: async (id: number) => {
    const response = await axios.get(`${API_URL}/invoices/${id}/download`, {
      headers: getAuthHeader(),
      responseType: 'blob',
    });
    return response.data;
  },

  email: async (id: number) => {
    const response = await axios.post(
      `${API_URL}/invoices/${id}/email`,
      {},
      { headers: getAuthHeader() }
    );
    return response.data;
  },

  markAsPaid: async (id: number, data: {
    payment_method: string;
    payment_reference?: string;
    amount: number;
  }) => {
    const response = await axios.put(
      `${API_URL}/invoices/${id}/mark-paid`,
      data,
      { headers: getAuthHeader() }
    );
    return response.data;
  },

  getStats: async () => {
    const response = await axios.get(`${API_URL}/invoices/stats`, {
      headers: getAuthHeader(),
    });
    return response.data;
  },
};

// ============= TAX APIs =============
export const taxApi = {
  calculate: async (data: {
    country: string;
    state?: string;
    city?: string;
    zip_code?: string;
    items: Array<{
      amount: number;
      category_id?: number;
      is_digital?: boolean;
    }>;
    shipping?: number;
    user_id?: number;
  }) => {
    const response = await axios.post(`${API_URL}/taxes/calculate`, data, {
      headers: getAuthHeader(),
    });
    return response.data;
  },

  getRates: async (country: string, state?: string, city?: string, zipCode?: string) => {
    const params = new URLSearchParams({ country });
    if (state) params.append('state', state);
    if (city) params.append('city', city);
    if (zipCode) params.append('zip_code', zipCode);

    const response = await axios.get(`${API_URL}/taxes/rates?${params}`, {
      headers: getAuthHeader(),
    });
    return response.data;
  },

  estimate: async (amount: number, country: string, state?: string) => {
    const response = await axios.post(
      `${API_URL}/taxes/estimate`,
      { amount, country, state },
      { headers: getAuthHeader() }
    );
    return response.data;
  },

  validateTaxId: async (taxId: string, country: string) => {
    const response = await axios.post(
      `${API_URL}/taxes/validate-id`,
      { tax_id: taxId, country },
      { headers: getAuthHeader() }
    );
    return response.data;
  },

  getExemptions: async () => {
    const response = await axios.get(`${API_URL}/taxes/exemptions`, {
      headers: getAuthHeader(),
    });
    return response.data;
  },
};

// ============= IMPORT/EXPORT APIs =============
export const importExportApi = {
  downloadTemplate: async (type: 'products' | 'orders') => {
    const response = await axios.get(`${API_URL}/import/template?type=${type}`, {
      headers: getAuthHeader(),
      responseType: 'blob',
    });
    return response.data;
  },

  validateImport: async (file: File) => {
    const formData = new FormData();
    formData.append('file', file);

    const response = await axios.post(`${API_URL}/import/validate`, formData, {
      headers: {
        ...getAuthHeader(),
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  importProducts: async (file: File, updateExisting: boolean = false) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('update_existing', String(updateExisting));

    const response = await axios.post(`${API_URL}/import/products`, formData, {
      headers: {
        ...getAuthHeader(),
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  exportProducts: async (filters?: {
    category_id?: number;
    status?: string;
    seller_id?: number;
  }) => {
    const response = await axios.post(`${API_URL}/export/products`, filters, {
      headers: getAuthHeader(),
      responseType: 'blob',
    });
    return response.data;
  },

  exportOrders: async (filters?: {
    start_date?: string;
    end_date?: string;
    status?: string;
    seller_id?: number;
  }) => {
    const response = await axios.post(`${API_URL}/export/orders`, filters, {
      headers: getAuthHeader(),
      responseType: 'blob',
    });
    return response.data;
  },

  exportCustomers: async (filters?: {
    registered_after?: string;
    has_orders?: boolean;
  }) => {
    const response = await axios.post(`${API_URL}/export/customers`, filters, {
      headers: getAuthHeader(),
      responseType: 'blob',
    });
    return response.data;
  },
};

export default {
  currency: currencyApi,
  invoice: invoiceApi,
  tax: taxApi,
  importExport: importExportApi,
};
