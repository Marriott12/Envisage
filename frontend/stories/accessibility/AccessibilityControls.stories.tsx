import type { Meta, StoryObj } from '@storybook/react';
import { AccessibilityControls } from '@/components/accessibility/AccessibilityControls';

const meta: Meta<typeof AccessibilityControls> = {
  title: 'Accessibility/AccessibilityControls',
  component: AccessibilityControls,
  parameters: {
    layout: 'fullscreen',
  },
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AccessibilityControls>;

export const Default: Story = {};

export const HighContrast: Story = {
  parameters: {
    backgrounds: { default: 'dark' },
  },
};
