export default definePageConfig(process.env.TARO_ENV === 'weapp' ? {
  navigationStyle: 'custom',
  navigationBarTitleText: '搜索商品',
} : {});
