import { PropsWithChildren, useEffect } from 'react';
import { isH5 } from './common/platform';
import { handleUnhandledAppError } from './common/error-feedback';
import './app.scss';

function App({ children }: PropsWithChildren) {
  useEffect(() => {
    if (!isH5() || typeof window === 'undefined') {
      return undefined;
    }

    const onUnhandledRejection = (event: PromiseRejectionEvent) => {
      if (handleUnhandledAppError(event.reason)) {
        event.preventDefault();
      }
    };

    const onError = (event: ErrorEvent) => {
      const reason = event.error || { message: event.message };
      if (handleUnhandledAppError(reason)) {
        event.preventDefault();
      }
    };

    window.addEventListener('unhandledrejection', onUnhandledRejection);
    window.addEventListener('error', onError);

    return () => {
      window.removeEventListener('unhandledrejection', onUnhandledRejection);
      window.removeEventListener('error', onError);
    };
  }, []);

  return children;
}

export default App;
