export default definePageConfig(process.env.TARO_ENV === 'weapp' ? {
  navigationStyle: 'custom',
  navigationBarTitleText: '发表评价',
} : {});
