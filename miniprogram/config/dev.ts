import type { UserConfigExport } from '@tarojs/cli';

export default {
  logger: {
    quiet: false,
    stats: true,
  },
  mini: {},
  h5: {
    devServer: {
      open: false,
    },
    webpack: (config) => {
      config.optimization = {
        ...(config.optimization ?? {}),
        minimize: false,
      };

      return config;
    },
  },
} satisfies UserConfigExport;
