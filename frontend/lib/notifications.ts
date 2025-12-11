import toast from 'react-hot-toast';

// Success notifications
export const notifySuccess = (message: string) => {
  toast.success(message, {
    duration: 3000,
    position: 'top-right',
  });
};

// Error notifications
export const notifyError = (message: string) => {
  toast.error(message, {
    duration: 4000,
    position: 'top-right',
  });
};

// Info notifications
export const notifyInfo = (message: string) => {
  toast(message, {
    duration: 3000,
    position: 'top-right',
    icon: 'ℹ️',
  });
};

// Loading notifications
export const notifyLoading = (message: string) => {
  return toast.loading(message, {
    position: 'top-right',
  });
};

// Dismiss notification
export const dismissNotification = (toastId: string) => {
  toast.dismiss(toastId);
};

// Promise notifications (auto-handles loading, success, error)
export const notifyPromise = <T,>(
  promise: Promise<T>,
  messages: {
    loading: string;
    success: string;
    error: string;
  }
) => {
  return toast.promise(
    promise,
    {
      loading: messages.loading,
      success: messages.success,
      error: messages.error,
    },
    {
      position: 'top-right',
    }
  );
};

export default {
  success: notifySuccess,
  error: notifyError,
  info: notifyInfo,
  loading: notifyLoading,
  dismiss: dismissNotification,
  promise: notifyPromise,
};
