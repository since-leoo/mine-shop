import { PropsWithChildren, useEffect } from 'react';
import { useDidShow, useLaunch } from '@tarojs/taro';
import { isH5 } from './common/platform';
import { handleUnhandledAppError } from './common/error-feedback';
import { notifyAppEntry } from './common/plugin-hooks';
import './plugins';
import './app.scss';

function App({ children }: PropsWithChildren) {
  useLaunch((options) => { void notifyAppEntry(options); });
  useDidShow((options) => { if (options) void notifyAppEntry(options); });

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

    const notifyLocation = () => { void notifyAppEntry(window.location.href); };
    notifyLocation();

    window.addEventListener('unhandledrejection', onUnhandledRejection);
    window.addEventListener('error', onError);
    window.addEventListener('hashchange', notifyLocation);
    window.addEventListener('popstate', notifyLocation);

    return () => {
      window.removeEventListener('unhandledrejection', onUnhandledRejection);
      window.removeEventListener('error', onError);
      window.removeEventListener('hashchange', notifyLocation);
      window.removeEventListener('popstate', notifyLocation);
    };
  }, []);

  return children;
}

export default App;
