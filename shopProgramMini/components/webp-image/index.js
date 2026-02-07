const windowInfo = wx.getWindowInfo();
const screenWidth = windowInfo.screenWidth || 375;
const pixelRatio = windowInfo.pixelRatio || 2;

function rpx2px(rpx) {
  return Math.round((rpx * screenWidth) / 750);
}

function getFileExt(src) {
  const fileUrl = src.split('?')[0];
  const parts = fileUrl.split('/');
  const filename = parts[parts.length - 1];
  return (filename.split('.')[1] || 'jpg').toLowerCase();
}

function imageMogr(url, options) {
  if (!url || typeof url !== 'string') return '';
  if (
    url.indexOf('qlogo.cn') !== -1 ||
    url.indexOf('wxfile://') === 0 ||
    url.indexOf('http://tmp/wx') === 0 ||
    url.indexOf('imageMogr2') !== -1
  ) {
    return url;
  }
  if (url.indexOf('http://') === 0) {
    url = url.replace('http://', 'https://');
  } else if (url.indexOf('//') === 0) {
    url = 'https:' + url;
  }
  if (!options) return url;

  const { format, quality = 70, strip = true, crop } = options;
  const width = Math.ceil(options.width);
  const height = Math.ceil(options.height);
  const isValidWidth = typeof width === 'number' && width > 0;
  const isValidHeight = typeof height === 'number' && height > 0;

  let imageMogrStr = '';
  let size = '';
  if (isValidWidth && isValidHeight) {
    size = `${width}x${height}`;
  } else if (isValidWidth) {
    size = `${width}x`;
  } else if (isValidHeight) {
    size = `x${height}`;
  }
  if (size) {
    imageMogrStr += `/${crop ? 'crop' : 'thumbnail'}/${size}`;
    if (crop) imageMogrStr += '/gravity/center';
  }
  if (typeof quality === 'number') {
    imageMogrStr += `/quality/${quality}`;
  }
  if (strip) imageMogrStr += '/strip';

  const ext = getFileExt(url);
  if (ext === 'gif') {
    imageMogrStr += '/cgif/1';
  } else if (format) {
    imageMogrStr += `/format/${format}`;
  }
  if (format === 'jpg' || (!format && (ext === 'jpg' || ext === 'jpeg'))) {
    imageMogrStr += '/interlace/1';
  }
  if (!imageMogrStr) return url;
  return `${url}${url.indexOf('?') !== -1 ? '&' : '?'}imageMogr2${imageMogrStr}`;
}

function buildSrc(src, thumbWidth, thumbHeight, mode, webp) {
  if (!src) return '';
  if (!thumbWidth && !thumbHeight) return '';
  return imageMogr(src, {
    width: mode !== 'heightFix' ? rpx2px(thumbWidth) * pixelRatio : null,
    height: mode !== 'widthFix' ? rpx2px(thumbHeight) * pixelRatio : null,
    format: webp ? 'webp' : null,
  });
}

Component({
  externalClasses: ['t-class', 't-class-load'],
  properties: {
    loadFailed: {
      type: String,
      value: 'default',
    },
    loading: {
      type: String,
      value: 'default',
    },
    src: {
      type: String,
      value: '',
    },
    mode: {
      type: String,
      value: 'aspectFill',
    },
    webp: {
      type: Boolean,
      value: true,
    },
    lazyLoad: {
      type: Boolean,
      value: false,
    },
    showMenuByLongpress: {
      type: Boolean,
      value: false,
    },
  },
  data: {
    _src: '',
  },
  lifetimes: {
    ready() {
      const { mode } = this.properties;
      this.getRect('.J-image-wrap').then((res) => {
        if (res) {
          const { width, height } = res;
          if (mode === 'heightFix') {
            this._thumbHeight = this.px2rpx(height) || 375;
            this._thumbWidth = 375;
          } else {
            this._thumbWidth = this.px2rpx(width) || 375;
            this._thumbHeight = 375;
          }
        } else {
          this._thumbWidth = 375;
          this._thumbHeight = 375;
        }
        this._ready = true;
        this.updateSrc();
      });
    },
  },
  observers: {
    'src, webp, mode'() {
      if (this._ready) {
        this.updateSrc();
      }
    },
  },
  methods: {
    px2rpx(px) {
      return (750 / screenWidth) * px;
    },
    getRect(selector) {
      return new Promise((resolve) => {
        if (!this.selectorQuery) {
          this.selectorQuery = this.createSelectorQuery();
        }
        this.selectorQuery.select(selector).boundingClientRect(resolve).exec();
      });
    },
    updateSrc() {
      const { src, mode, webp } = this.properties;
      const _src = buildSrc(src, this._thumbWidth || 375, this._thumbHeight || 375, mode, webp);
      this.setData({ _src });
    },
    onLoad(e) {
      this.triggerEvent('load', e.detail);
    },
    onError(e) {
      this.triggerEvent('error', e.detail);
    },
  },
});
