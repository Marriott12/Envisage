import type { Meta, StoryObj } from '@storybook/react';
import { MultiStepCheckout } from '@/components/checkout/MultiStepCheckout';

const meta: Meta<typeof MultiStepCheckout> = {
  title: 'Checkout/MultiStepCheckout',
  component: MultiStepCheckout,
  parameters: {
    layout: 'fullscreen',
  },
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof MultiStepCheckout>;

export const Default: Story = {};

export const WithCartItems: Story = {
  parameters: {
    mockData: {
      cart: {
        items: [
          {
            id: '1',
            title: 'iPhone 15 Pro Max',
            price: 1199,
            quantity: 1,
            image: '/images/products/iphone-15-pro.jpg',
          },
          {
            id: '2',
            title: 'AirPods Pro',
            price: 249,
            quantity: 2,
            image: '/images/products/airpods-pro.jpg',
          },
        ],
      },
    },
  },
};

export const MobileView: Story = {
  parameters: {
    viewport: {
      defaultViewport: 'mobile',
    },
  },
};
